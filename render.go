package main

import (
	"fmt"
	"math"
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"sync"
	"time"
)

// geometry holds every resolved page measurement, in inches.
type geometry struct {
	paperWidth, paperHeight      float64
	marginTop, marginBottom      float64
	marginLeft, marginRight      float64
	headerHeight, footerHeight   float64
	headerSpacing, footerSpacing float64
	headerOffset, footerOffset   float64
	landscape                    bool
}

// resolveGeometry converts the string dimension flags into inches and applies
// the --margin shorthand.
func resolveGeometry(cfg *config) (*geometry, error) {
	w, h, err := GetPaperDimensions(cfg.paperSize, cfg.orientation)
	if err != nil {
		return nil, err
	}

	// --margin seeds all four sides; an explicit side still wins because the
	// per-side flags keep their "0" default only when untouched.
	if cfg.margin != "" {
		for _, side := range []*string{&cfg.marginTop, &cfg.marginBottom, &cfg.marginLeft, &cfg.marginRight} {
			if *side == "0" {
				*side = cfg.margin
			}
		}
	}

	parse := func(name, value string) (float64, error) {
		v, err := ParseDimensionToInches(value)
		if err != nil {
			return 0, fmt.Errorf("invalid --%s: %w", name, err)
		}
		if v < 0 {
			return 0, fmt.Errorf("invalid --%s: must not be negative", name)
		}
		return v, nil
	}

	g := &geometry{paperWidth: w, paperHeight: h}
	g.landscape = isLandscape(cfg.orientation)

	for _, f := range []struct {
		name string
		src  string
		dst  *float64
	}{
		{"margin-top", cfg.marginTop, &g.marginTop},
		{"margin-bottom", cfg.marginBottom, &g.marginBottom},
		{"margin-left", cfg.marginLeft, &g.marginLeft},
		{"margin-right", cfg.marginRight, &g.marginRight},
		{"header-height", cfg.headerHeight, &g.headerHeight},
		{"footer-height", cfg.footerHeight, &g.footerHeight},
		{"header-spacing", cfg.headerSpacing, &g.headerSpacing},
		{"footer-spacing", cfg.footerSpacing, &g.footerSpacing},
		{"header-offset", cfg.headerOffset, &g.headerOffset},
		{"footer-offset", cfg.footerOffset, &g.footerOffset},
	} {
		v, err := parse(f.name, f.src)
		if err != nil {
			return nil, err
		}
		*f.dst = v
	}

	// Guard against a layout with no room left for content. Mirrors the
	// max() the renderer uses, since bands overlap the margin rather than
	// stacking on top of it.
	topUsed := math.Max(g.marginTop, g.headerOffset+g.headerHeight+g.headerSpacing)
	bottomUsed := math.Max(g.marginBottom, g.footerOffset+g.footerHeight+g.footerSpacing)
	usableV := g.paperHeight - topUsed - bottomUsed
	if usableV <= 0.2 {
		return nil, fmt.Errorf("margins, header and footer leave no room for content on a %.2fin tall page", g.paperHeight)
	}
	if g.paperWidth-g.marginLeft-g.marginRight <= 0.2 {
		return nil, fmt.Errorf("left and right margins leave no room for content on a %.2fin wide page", g.paperWidth)
	}

	return g, nil
}

func isLandscape(orientation string) bool {
	return equalFoldTrim(orientation, "landscape")
}

// job carries everything one conversion needs.
type job struct {
	cfg      *config
	geo      *geometry
	renderer *ChromeRenderer
	tempDir  string
	baseDir  string
	rawLogf  func(string, ...interface{})

	// Guards stderr, which the concurrent band renders share.
	mu sync.Mutex
}

// logf writes one progress line. Bands render concurrently, so every caller
// must emit a complete line in a single call.
func (j *job) logf(format string, args ...interface{}) {
	j.mu.Lock()
	defer j.mu.Unlock()
	j.rawLogf(format, args...)
}

func (j *job) timeout() time.Duration {
	return time.Duration(j.cfg.timeoutSeconds) * time.Second
}

// step times one stage of the pipeline. With --timings it reports how long each
// stage took, which is the quickest way to see whether a slow run is Chrome
// rendering, PDF stamping, or the document itself.
//
// Safe to call from the concurrent band renders.
func (j *job) step(name string, fn func() error) error {
	start := time.Now()
	err := fn()
	if j.cfg.timings {
		j.mu.Lock()
		fmt.Fprintf(os.Stderr, "  [timing] %-24s %8.1f ms\n", name, float64(time.Since(start).Microseconds())/1000)
		j.mu.Unlock()
	}
	return err
}

// build runs the full pipeline and returns the path of the finished PDF.
func (j *job) build(contentHTML, headerHTML, footerHTML, watermarkHTML string) (string, int, error) {
	g := j.geo

	// Content clears whichever is deeper: the page margin, or the band itself.
	// The band starts at the paper edge, so adding the two would double-count
	// the margin and leave a large empty strip under the header.
	contentTop := g.marginTop
	if headerHTML != "" {
		contentTop = math.Max(contentTop, g.headerOffset+g.headerHeight+g.headerSpacing)
	}
	contentBottom := g.marginBottom
	if footerHTML != "" {
		contentBottom = math.Max(contentBottom, g.footerOffset+g.footerHeight+g.footerSpacing)
	}

	// Start Chrome before the first render so --timings attributes browser
	// startup to its own line instead of hiding it inside the content render.
	if err := j.step("start chrome", j.renderer.Start); err != nil {
		return "", 0, err
	}

	j.logf("Rendering content... ")
	contentPDF := filepath.Join(j.tempDir, "content.pdf")
	err := j.step("render content", func() error {
		return j.renderer.RenderHTMLToPDF(contentHTML, contentPDF, RenderOptions{
			PaperWidthInches:   g.paperWidth,
			PaperHeightInches:  g.paperHeight,
			MarginTopInches:    contentTop,
			MarginBottomInches: contentBottom,
			MarginLeftInches:   g.marginLeft,
			MarginRightInches:  g.marginRight,
			Landscape:          g.landscape,
			Scale:              j.cfg.scale,
			PreferCSSPageSize:  j.cfg.preferCSSPageSize,
			BaseDir:            j.baseDir,
			Timeout:            j.timeout(),
		})
	})
	if err != nil {
		j.logf("failed\n")
		return "", 0, fmt.Errorf("failed to render content: %w", err)
	}
	j.logf("done\n")

	// Everything below runs against one in-memory document: load once, stamp
	// header/footer/watermark, set metadata, write once.
	var comp *Composer
	if err := j.step("load pdf", func() error {
		var e error
		comp, e = NewComposer(contentPDF)
		return e
	}); err != nil {
		return "", 0, err
	}
	totalPages := comp.PageCount()

	// Header, footer and watermark are independent of each other, so render
	// them concurrently as separate tabs in the one browser. They are all
	// produced before any stamping, so a band page-count mismatch fails fast
	// while the document is still untouched.
	var (
		headerBand, footerBand *band
		wmPDF                  string
		wg                     sync.WaitGroup
		mu                     sync.Mutex
		errs                   []error
	)
	fail := func(err error) {
		mu.Lock()
		errs = append(errs, err)
		mu.Unlock()
	}

	if headerHTML != "" {
		wg.Add(1)
		go func() {
			defer wg.Done()
			b, err := j.renderBand(headerHTML, totalPages, bandSpec{
				name:   "header",
				height: g.headerHeight,
				// Flush with the top edge of the paper, like wkhtmltopdf, so a
				// full-bleed header band has no white strip above it.
				// --header-offset pushes it down for designs that want to sit
				// inside the margin.
				placement: StampPlacement{Pos: "tc", OffsetY: -InchesToPoints(g.headerOffset)},
				fallback:  1.0,
			})
			if err != nil {
				fail(err)
				return
			}
			headerBand = b
		}()
	}

	if footerHTML != "" {
		wg.Add(1)
		go func() {
			defer wg.Done()
			b, err := j.renderBand(footerHTML, totalPages, bandSpec{
				name:      "footer",
				height:    g.footerHeight,
				placement: StampPlacement{Pos: "bc", OffsetY: InchesToPoints(g.footerOffset)},
				fallback:  0.6,
			})
			if err != nil {
				fail(err)
				return
			}
			footerBand = b
		}()
	}

	if watermarkHTML != "" {
		wmPDF = filepath.Join(j.tempDir, "watermark.pdf")
		wg.Add(1)
		go func() {
			defer wg.Done()
			if err := j.step("render watermark", func() error {
				return j.renderer.RenderHTMLToPDF(watermarkHTML, wmPDF, RenderOptions{
					PaperWidthInches:  g.paperWidth,
					PaperHeightInches: g.paperHeight,
					Landscape:         g.landscape,
					Scale:             1.0,
					BaseDir:           j.baseDir,
					Timeout:           j.timeout(),
				})
			}); err != nil {
				fail(fmt.Errorf("failed to render watermark: %w", err))
			}
		}()
	}

	wg.Wait()
	if len(errs) > 0 {
		return "", 0, errs[0]
	}

	for _, b := range []*band{headerBand, footerBand} {
		if b == nil {
			continue
		}
		if err := j.step("stamp "+b.spec.name, func() error {
			return comp.StampBand(b.path, b.spec.placement, b.multi)
		}); err != nil {
			return "", 0, err
		}
	}

	if wmPDF != "" {
		onTop := !j.cfg.watermarkBehind
		if err := j.step("stamp watermark", func() error {
			return comp.Watermark(wmPDF, j.cfg.watermarkOpacity, onTop)
		}); err != nil {
			return "", 0, err
		}
	}

	if meta := j.metadata(); len(meta) > 0 {
		if err := j.step("write metadata", func() error {
			return comp.SetMetadata(meta)
		}); err != nil {
			return "", 0, err
		}
	}

	out := filepath.Join(j.tempDir, "final.pdf")
	if err := j.step("write pdf", func() error {
		return comp.WriteFile(out)
	}); err != nil {
		return "", 0, err
	}

	return out, totalPages, nil
}

func (j *job) metadata() map[string]string {
	meta := map[string]string{}
	for k, v := range map[string]string{
		"Title":    j.cfg.title,
		"Author":   j.cfg.author,
		"Subject":  j.cfg.subject,
		"Keywords": j.cfg.keywords,
	} {
		if v != "" {
			meta[k] = v
		}
	}
	return meta
}

var (
	headRe    = regexp.MustCompile(`(?is)<head[^>]*>(.*?)</head>`)
	bodyRe    = regexp.MustCompile(`(?is)<body([^>]*)>(.*?)</body>`)
	doctypeRe = regexp.MustCompile(`(?is)<!doctype[^>]*>`)
	htmlTagRe = regexp.MustCompile(`(?is)</?html[^>]*>`)
)

// buildPagedBandHTML expands a header/footer template into one document that
// paginates into exactly totalPages pages, each carrying that page's numbers.
//
// The template's own <head> is hoisted so its styles and fonts load once, and
// its <body> markup is repeated inside fixed-height blocks with a forced page
// break between them. Body attributes are re-applied per block so styling that
// hangs off <body> still takes effect.
func buildPagedBandHTML(templateHTML string, totalPages int, heightInches float64, pageOffset, totalOffset int) string {
	head := ""
	if m := headRe.FindStringSubmatch(templateHTML); m != nil {
		head = m[1]
	}

	bodyAttrs, bodyInner := "", templateHTML
	if m := bodyRe.FindStringSubmatch(templateHTML); m != nil {
		bodyAttrs, bodyInner = m[1], m[2]
	} else {
		// No <body>: strip the document scaffolding and use what remains.
		bodyInner = headRe.ReplaceAllString(bodyInner, "")
		bodyInner = doctypeRe.ReplaceAllString(bodyInner, "")
		bodyInner = htmlTagRe.ReplaceAllString(bodyInner, "")
	}

	var sb strings.Builder
	sb.Grow(len(bodyInner)*totalPages + len(head) + 512)

	sb.WriteString(`<!DOCTYPE html><html><head><meta charset="utf-8">`)
	sb.WriteString(head)
	// height:Xin with overflow:hidden pins each block to one page so Chrome's
	// pagination lines up 1:1 with the content pages.
	fmt.Fprintf(&sb, `<style>
html,body{margin:0;padding:0}
.snpdf-band{height:%.4fin;overflow:hidden;position:relative;break-after:page;page-break-after:always}
.snpdf-band:last-child{break-after:auto;page-break-after:auto}
</style></head><body>`, heightInches)

	for p := 1; p <= totalPages; p++ {
		rendered := replacePagePlaceholders(bodyInner, p+pageOffset, totalPages+totalOffset)
		sb.WriteString(`<div class="snpdf-band"`)
		if strings.TrimSpace(bodyAttrs) != "" {
			sb.WriteString(" " + strings.TrimSpace(bodyAttrs))
		}
		sb.WriteString(">")
		sb.WriteString(rendered)
		sb.WriteString("</div>")
	}

	sb.WriteString("</body></html>")
	return sb.String()
}

type bandSpec struct {
	name      string
	height    float64
	placement StampPlacement
	fallback  float64
}

// band is a rendered header or footer ready to be stamped.
type band struct {
	spec  bandSpec
	path  string
	multi bool // true when path holds one page per document page
}

// renderBand renders a header or footer to a PDF in exactly one Chrome call.
//
// A static template becomes a single page reused everywhere. A template with
// page numbers is expanded into one document holding one band per page,
// separated by forced page breaks, which Chrome paginates in a single pass.
// Rendering page by page is orders of magnitude slower on long documents.
func (j *job) renderBand(templateHTML string, totalPages int, spec bandSpec) (*band, error) {
	height := spec.height
	if height <= 0 {
		height = spec.fallback
	}

	renderOpts := RenderOptions{
		PaperWidthInches:  j.geo.paperWidth,
		PaperHeightInches: height,
		Landscape:         false, // width/height are already final
		Scale:             1.0,
		BaseDir:           j.baseDir,
		Timeout:           j.timeout(),
	}

	multi := hasPagePlaceholder(templateHTML)
	html := templateHTML
	note := fmt.Sprintf("1 render, reused on %d pages", totalPages)
	if multi {
		note = fmt.Sprintf("1 render for %d pages", totalPages)
		html = buildPagedBandHTML(templateHTML, totalPages, height, j.cfg.pageOffset, j.cfg.totalOffset)
	}

	path := filepath.Join(j.tempDir, spec.name+".pdf")
	if err := j.step("render "+spec.name, func() error {
		return j.renderer.RenderHTMLToPDF(html, path, renderOpts)
	}); err != nil {
		// Bands render concurrently, so each logs one complete line rather
		// than a prefix that another goroutine could interleave with.
		j.logf("Rendering %s... failed\n", spec.name)
		return nil, fmt.Errorf("failed to render %s: %w", spec.name, err)
	}

	if multi {
		got, err := GetPDFPageCount(path)
		if err != nil {
			return nil, err
		}
		if got != totalPages {
			// Content taller than the band can push a block onto an extra page,
			// which would misalign every page number after it.
			return nil, fmt.Errorf("%s produced %d pages for a %d page document; reduce the %s content or increase --%s-height",
				spec.name, got, totalPages, spec.name, spec.name)
		}
	}
	j.logf("Rendering %s (%s)... done\n", spec.name, note)

	return &band{spec: spec, path: path, multi: multi}, nil
}
