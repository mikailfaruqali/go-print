package main

import (
	"context"
	"encoding/base64"
	"fmt"
	"io"
	"net"
	"net/http"
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"sync"
	"time"

	"github.com/chromedp/cdproto"
	"github.com/chromedp/cdproto/cdp"
	"github.com/chromedp/cdproto/emulation"
	cdpio "github.com/chromedp/cdproto/io"
	"github.com/chromedp/cdproto/page"
	"github.com/chromedp/cdproto/runtime"
	"github.com/chromedp/chromedp"
)

// ChromeRenderer owns a single long-lived headless browser. Every render reuses
// that browser via a fresh tab, which is what keeps large documents fast: the
// old behaviour launched a whole Chrome process per page.
type ChromeRenderer struct {
	chromePath  string
	allocCtx    context.Context
	allocCancel context.CancelFunc

	browserCtx    context.Context
	browserCancel context.CancelFunc
	browserOnce   sync.Once
	browserErr    error

	server     *assetServer
	serverOnce sync.Once
	serverErr  error

	quiet bool
}

// NewChromeRenderer creates a renderer bound to the given Chrome binary. The
// browser itself is started lazily on the first render.
func NewChromeRenderer(chromePath string, quiet bool) (*ChromeRenderer, error) {
	opts := append(chromedp.DefaultExecAllocatorOptions[:],
		chromedp.ExecPath(chromePath),
		chromedp.NoSandbox,
		chromedp.DisableGPU,
		chromedp.Flag("disable-background-networking", true),
		chromedp.Flag("disable-default-apps", true),
		chromedp.Flag("disable-extensions", true),
		chromedp.Flag("disable-sync", true),
		chromedp.Flag("disable-translate", true),
		chromedp.Flag("headless", true),
		chromedp.Flag("hide-scrollbars", true),
		chromedp.Flag("metrics-recording-only", true),
		chromedp.Flag("mute-audio", true),
		chromedp.Flag("no-first-run", true),
		chromedp.Flag("safebrowsing-disable-auto-update", true),
		chromedp.Flag("font-render-hinting", "none"),
		chromedp.Flag("disable-breakpad", true),
		chromedp.Flag("disable-component-update", true),
		chromedp.Flag("disable-domain-reliability", true),
		chromedp.Flag("disable-client-side-phishing-detection", true),
		chromedp.Flag("disable-ipc-flooding-protection", true),
		chromedp.Flag("no-default-browser-check", true),
		chromedp.Flag("no-pings", true),
		chromedp.Flag("password-store", "basic"),
		chromedp.Flag("use-mock-keychain", true),
		chromedp.Flag("disable-features", "Translate,OptimizationHints,MediaRouter,DialLocalDiscovery"),
		// Keep background tabs rendering at full speed; without these Chrome
		// throttles the tabs we print from and pages come out half-painted.
		chromedp.Flag("disable-background-timer-throttling", true),
		chromedp.Flag("disable-backgrounding-occluded-windows", true),
		chromedp.Flag("disable-renderer-backgrounding", true),
		chromedp.Flag("run-all-compositor-stages-before-draw", true),
		// Printing never rasterises to the screen, so the tile/raster machinery
		// is overhead on a long paginated document.
		chromedp.Flag("disable-software-rasterizer", true),
		chromedp.Flag("disable-lcd-text", true),
		chromedp.Flag("disable-dev-shm-usage", true),
		chromedp.Flag("disable-hang-monitor", true),
		chromedp.Flag("disable-back-forward-cache", true),
		chromedp.Flag("disable-logging", true),
		chromedp.Flag("log-level", "3"),
	)

	allocCtx, allocCancel := chromedp.NewExecAllocator(context.Background(), opts...)
	return &ChromeRenderer{
		chromePath:  chromePath,
		allocCtx:    allocCtx,
		allocCancel: allocCancel,
		quiet:       quiet,
	}, nil
}

func discard(string, ...interface{}) {}

// Start launches the shared browser. Calling it is optional - the first render
// starts the browser anyway - but doing it up front keeps startup cost visible
// and lets it overlap with other setup work.
func (cr *ChromeRenderer) Start() error { return cr.ensureBrowser() }

// ensureServer starts the local asset server exactly once. Renders can run
// concurrently, so this must not be a bare nil check.
func (cr *ChromeRenderer) ensureServer() error {
	cr.serverOnce.Do(func() {
		cr.server, cr.serverErr = newAssetServer()
	})
	return cr.serverErr
}

// ensureBrowser starts the shared browser exactly once.
func (cr *ChromeRenderer) ensureBrowser() error {
	cr.browserOnce.Do(func() {
		ctx, cancel := chromedp.NewContext(cr.allocCtx, chromedp.WithLogf(discard), chromedp.WithErrorf(discard))
		// chromedp starts the browser lazily too, so force it up now and let a
		// failure here surface as a clear error instead of a timeout later.
		if err := chromedp.Run(ctx); err != nil {
			cancel()
			cr.browserErr = fmt.Errorf("failed to start Chrome at %s: %w", cr.chromePath, err)
			return
		}
		cr.browserCtx = ctx
		cr.browserCancel = cancel
	})
	return cr.browserErr
}

// Close shuts down the browser and the asset server.
func (cr *ChromeRenderer) Close() {
	if cr.browserCancel != nil {
		cr.browserCancel()
	}
	if cr.allocCancel != nil {
		cr.allocCancel()
	}
	if cr.server != nil {
		cr.server.Close()
	}
}

// RenderOptions configures print settings for one HTML render.
type RenderOptions struct {
	PaperWidthInches   float64
	PaperHeightInches  float64
	MarginTopInches    float64
	MarginBottomInches float64
	MarginLeftInches   float64
	MarginRightInches  float64
	Landscape          bool
	Scale              float64
	PreferCSSPageSize  bool
	Timeout            time.Duration
	// BaseDir is the directory relative URLs in the HTML resolve against.
	BaseDir string
}

// assetServer serves the document's base directory over loopback HTTP so that
// relative <img>, <link> and @font-face URLs resolve. A data: URI has no base,
// which silently dropped every relative asset.
type assetServer struct {
	listener net.Listener
	srv      *http.Server
	origin   string

	mu    sync.Mutex
	roots map[string]string // token -> directory
	pages map[string]string // token/path -> html body
	seq   int
}

func newAssetServer() (*assetServer, error) {
	ln, err := net.Listen("tcp", "127.0.0.1:0")
	if err != nil {
		return nil, fmt.Errorf("failed to start local asset server: %w", err)
	}
	as := &assetServer{
		listener: ln,
		roots:    map[string]string{},
		pages:    map[string]string{},
		origin:   "http://" + ln.Addr().String(),
	}
	mux := http.NewServeMux()
	mux.HandleFunc("/", as.handle)
	as.srv = &http.Server{Handler: mux}
	go as.srv.Serve(ln)
	return as, nil
}

func (as *assetServer) Close() {
	if as.srv != nil {
		ctx, cancel := context.WithTimeout(context.Background(), 2*time.Second)
		defer cancel()
		as.srv.Shutdown(ctx)
	}
}

// register stores an HTML document plus its base directory and returns the URL
// Chrome should navigate to.
func (as *assetServer) register(html string, baseDir string) string {
	as.mu.Lock()
	defer as.mu.Unlock()
	as.seq++
	token := fmt.Sprintf("d%d", as.seq)
	as.roots[token] = baseDir
	as.pages[token] = html
	return fmt.Sprintf("%s/%s/", as.origin, token)
}

func (as *assetServer) handle(w http.ResponseWriter, r *http.Request) {
	// URL shape: /<token>/            -> the HTML document
	//            /<token>/some/asset  -> file relative to that document's dir
	trimmed := strings.TrimPrefix(r.URL.Path, "/")
	token, rest, _ := strings.Cut(trimmed, "/")

	as.mu.Lock()
	root, okRoot := as.roots[token]
	html, okPage := as.pages[token]
	as.mu.Unlock()

	if !okRoot {
		http.NotFound(w, r)
		return
	}

	if rest == "" {
		if !okPage {
			http.NotFound(w, r)
			return
		}
		w.Header().Set("Content-Type", "text/html; charset=utf-8")
		w.Header().Set("Cache-Control", "no-store")
		io.WriteString(w, html)
		return
	}

	if root == "" {
		http.NotFound(w, r)
		return
	}

	// Resolve and confine the request to the base directory.
	clean := filepath.Clean(filepath.FromSlash(rest))
	if filepath.IsAbs(clean) || strings.HasPrefix(clean, "..") {
		http.Error(w, "forbidden", http.StatusForbidden)
		return
	}
	full := filepath.Join(root, clean)
	absRoot, err1 := filepath.Abs(root)
	absFull, err2 := filepath.Abs(full)
	if err1 != nil || err2 != nil || !strings.HasPrefix(absFull, absRoot) {
		http.Error(w, "forbidden", http.StatusForbidden)
		return
	}

	w.Header().Set("Cache-Control", "no-store")
	http.ServeFile(w, r, absFull)
}

// RenderHTMLToPDF renders an HTML string to a PDF file at outputPath.
func (cr *ChromeRenderer) RenderHTMLToPDF(htmlContent string, outputPath string, opts RenderOptions) error {
	pdfBuf, err := cr.RenderHTMLToPDFBytes(htmlContent, opts)
	if err != nil {
		return err
	}
	if err := os.WriteFile(outputPath, pdfBuf, 0644); err != nil {
		return fmt.Errorf("failed to write rendered PDF to %s: %w", outputPath, err)
	}
	return nil
}

// RenderHTMLToPDFBytes renders an HTML string and returns the PDF bytes.
func (cr *ChromeRenderer) RenderHTMLToPDFBytes(htmlContent string, opts RenderOptions) ([]byte, error) {
	buf, _, err := cr.renderPDF(htmlContent, opts, nil)
	return buf, err
}

// RenderHTMLToPDFBytesCounted renders an HTML string and, as soon as Chrome has
// laid the document out, reports the printed page count on pageCount before the
// (much slower) serialisation of every page finishes.
//
// That early signal is what lets header/footer rendering - which needs the total
// page count - overlap with the content print instead of waiting for it.
func (cr *ChromeRenderer) RenderHTMLToPDFBytesCounted(htmlContent string, opts RenderOptions, pageCount chan<- int) ([]byte, error) {
	buf, _, err := cr.renderPDF(htmlContent, opts, pageCount)
	return buf, err
}

func (cr *ChromeRenderer) renderPDF(htmlContent string, opts RenderOptions, pageCount chan<- int) ([]byte, int, error) {
	// Whatever happens, never leave a waiting band render blocked on a count
	// that is no longer coming.
	counted := false
	emit := func(n int) {
		if pageCount != nil && !counted {
			counted = true
			pageCount <- n
		}
	}
	defer func() {
		if pageCount != nil && !counted {
			close(pageCount)
		}
	}()

	if err := cr.ensureBrowser(); err != nil {
		return nil, 0, err
	}

	timeout := opts.Timeout
	if timeout <= 0 {
		timeout = 120 * time.Second
	}

	if err := cr.ensureServer(); err != nil {
		return nil, 0, err
	}
	navURL := cr.server.register(htmlContent, opts.BaseDir)

	// A new tab on the existing browser, not a new browser process. Logger
	// options belong to the browser context and cannot be repeated here.
	ctx, cancel := chromedp.NewContext(cr.browserCtx)
	defer cancel()

	ctx, cancelTimeout := context.WithTimeout(ctx, timeout)
	defer cancelTimeout()

	scale := opts.Scale
	if scale <= 0 {
		scale = 1.0
	}

	var pdfBuf []byte
	var nPages int
	err := chromedp.Run(ctx,
		// Emulate the print media type so @media print rules apply, matching
		// what users expect from wkhtmltopdf.
		emulation.SetEmulatedMedia().WithMedia("print"),
		chromedp.Navigate(navURL),
		chromedp.WaitReady("body", chromedp.ByQuery),
		chromedp.ActionFunc(func(ctx context.Context) error {
			return waitForAssets(ctx)
		}),
		chromedp.ActionFunc(func(ctx context.Context) error {
			params := page.PrintToPDF().
				WithPrintBackground(true).
				WithPreferCSSPageSize(opts.PreferCSSPageSize).
				WithPaperWidth(opts.PaperWidthInches).
				WithPaperHeight(opts.PaperHeightInches).
				WithMarginTop(opts.MarginTopInches).
				WithMarginBottom(opts.MarginBottomInches).
				WithMarginLeft(opts.MarginLeftInches).
				WithMarginRight(opts.MarginRightInches).
				WithLandscape(opts.Landscape).
				WithScale(scale).
				WithDisplayHeaderFooter(false). // never use CDP native header/footer
				// Large documents exceed the CDP message size limit and come
				// back as an empty buffer; streaming avoids that entirely.
				WithTransferMode(page.PrintToPDFTransferModeReturnAsStream)

			data, stream, err := params.Do(ctx)
			if err != nil {
				return err
			}
			if stream == "" {
				pdfBuf = data
				n, _ := countPDFPages(data)
				nPages = n
				emit(n)
				return nil
			}
			defer cdpio.Close(stream).Do(ctx)

			out, err := readStream(ctx, stream)
			if err != nil {
				return err
			}
			pdfBuf = out
			// Chrome writes /Type/Page objects as it goes, so a cheap scan of the
			// finished bytes beats a full pdfcpu parse for the count the bands need.
			n, err := countPDFPages(out)
			if err != nil {
				return err
			}
			nPages = n
			emit(n)
			return nil
		}),
	)
	if err != nil {
		return nil, 0, fmt.Errorf("chromedp render error: %w", err)
	}
	if len(pdfBuf) == 0 {
		return nil, 0, fmt.Errorf("chrome produced an empty PDF")
	}

	return pdfBuf, nPages, nil
}

// pageObjRe matches the page objects Chrome emits, e.g. "/Type /Page" but not
// "/Type /Pages". Chrome's PDF output is uncompressed at the object level for
// these dictionaries, so counting them is exact and far cheaper than a full parse.
var pageObjRe = regexp.MustCompile(`/Type\s*/Page[^s]`)

// countPDFPages counts pages by scanning for page objects, falling back to a
// full parse if the scan finds nothing (a differently-encoded producer).
func countPDFPages(data []byte) (int, error) {
	if n := len(pageObjRe.FindAll(data, -1)); n > 0 {
		return n, nil
	}
	return GetPDFPageCountFromBytes(data)
}

// readStream drains a CDP IO stream. The generated ReadParams.Do drops the
// base64Encoded flag, and PDF chunks are always base64, so decode explicitly.
func readStream(ctx context.Context, handle cdpio.StreamHandle) ([]byte, error) {
	var out []byte
	for {
		var res cdpio.ReadReturns
		params := cdpio.Read(handle).WithSize(10 << 20)
		if err := cdp.Execute(ctx, cdproto.CommandIORead, params, &res); err != nil {
			return nil, fmt.Errorf("failed to read PDF stream: %w", err)
		}
		if res.Base64encoded {
			decoded, err := base64.StdEncoding.DecodeString(res.Data)
			if err != nil {
				return nil, fmt.Errorf("failed to decode PDF stream chunk: %w", err)
			}
			out = append(out, decoded...)
		} else {
			out = append(out, res.Data...)
		}
		if res.EOF {
			return out, nil
		}
	}
}

// waitForAssets blocks until webfonts are loaded and every image has either
// decoded or failed. Uses requestAnimationFrame inside the browser event loop
// rather than an arbitrary wall-clock sleep.
func waitForAssets(ctx context.Context) error {
	const script = `
new Promise((resolve) => {
  const onReady = () => {
    if (window.requestAnimationFrame) {
      requestAnimationFrame(() => resolve(true));
    } else {
      resolve(true);
    }
  };
  const done = () => {
    const imgs = Array.from(document.images || []);
    const pending = imgs.filter((i) => !i.complete);
    if (pending.length === 0) {
      onReady();
      return;
    }
    let left = pending.length;
    const tick = () => {
      if (--left <= 0) onReady();
    };
    pending.forEach((i) => {
      i.addEventListener('load', tick, { once: true });
      i.addEventListener('error', tick, { once: true });
    });
  };
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(done).catch(done);
  } else {
    done();
  }
})`
	var ok bool
	// Bound the asset wait so one broken remote URL cannot hang the render.
	waitCtx, cancel := context.WithTimeout(ctx, 20*time.Second)
	defer cancel()
	_ = chromedp.Evaluate(script, &ok, func(p *runtime.EvaluateParams) *runtime.EvaluateParams {
		return p.WithAwaitPromise(true)
	}).Do(waitCtx)
	return nil
}
