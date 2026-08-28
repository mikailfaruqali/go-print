package main

import (
	"context"
	"encoding/base64"
	"fmt"
	"os"
	"time"

	"github.com/chromedp/cdproto/page"
	"github.com/chromedp/chromedp"
)

// ChromeRenderer wraps chromedp operations with a persistent or reusable browser instance
type ChromeRenderer struct {
	chromePath  string
	allocCtx    context.Context
	allocCancel context.CancelFunc
}

// NewChromeRenderer creates an initialized ChromeRenderer
func NewChromeRenderer(chromePath string) (*ChromeRenderer, error) {
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
	)

	// Suppress verbose unhandled CDP event logs by using chromedp.WithLogf(func(string, ...interface{}) {})
	allocCtx, allocCancel := chromedp.NewExecAllocator(context.Background(), opts...)
	return &ChromeRenderer{
		chromePath:  chromePath,
		allocCtx:    allocCtx,
		allocCancel: allocCancel,
	}, nil
}

// Close shuts down the browser instance
func (cr *ChromeRenderer) Close() {
	if cr.allocCancel != nil {
		cr.allocCancel()
	}
}

// RenderOptions configures print settings for HTML rendering
type RenderOptions struct {
	PaperWidthInches   float64
	PaperHeightInches  float64
	MarginTopInches    float64
	MarginBottomInches float64
	MarginLeftInches   float64
	MarginRightInches  float64
	Landscape          bool
	Timeout            time.Duration
}

// RenderHTMLToPDF renders an HTML string to a PDF file at outputPath.
func (cr *ChromeRenderer) RenderHTMLToPDF(htmlContent string, outputPath string, opts RenderOptions) error {
	timeout := opts.Timeout
	if timeout <= 0 {
		timeout = 60 * time.Second
	}

	// Quiet chromedp logging for cleanly formatted CLI output
	ctx, cancel := chromedp.NewContext(cr.allocCtx, chromedp.WithLogf(func(string, ...interface{}) {}))
	defer cancel()

	ctx, cancel = context.WithTimeout(ctx, timeout)
	defer cancel()

	// Robust base64 data URI encoding to prevent corrupting SVG, base64 images, or utf-8 characters
	encodedHTML := base64.StdEncoding.EncodeToString([]byte(htmlContent))
	dataURI := "data:text/html;charset=utf-8;base64," + encodedHTML

	var pdfBuf []byte
	printParams := page.PrintToPDF().
		WithPrintBackground(true).
		WithPreferCSSPageSize(false).
		WithPaperWidth(opts.PaperWidthInches).
		WithPaperHeight(opts.PaperHeightInches).
		WithMarginTop(opts.MarginTopInches).
		WithMarginBottom(opts.MarginBottomInches).
		WithMarginLeft(opts.MarginLeftInches).
		WithMarginRight(opts.MarginRightInches).
		WithLandscape(opts.Landscape).
		WithDisplayHeaderFooter(false) // critical: never use CDP native header/footer

	err := chromedp.Run(ctx,
		chromedp.Navigate(dataURI),
		chromedp.WaitReady("body", chromedp.ByQuery),
		chromedp.Sleep(100*time.Millisecond), // ensure styles/fonts/images are painted
		chromedp.ActionFunc(func(ctx context.Context) error {
			var err error
			pdfBuf, _, err = printParams.Do(ctx)
			return err
		}),
	)
	if err != nil {
		return fmt.Errorf("chromedp render error: %w", err)
	}

	if err := os.WriteFile(outputPath, pdfBuf, 0644); err != nil {
		return fmt.Errorf("failed to write rendered PDF to %s: %w", outputPath, err)
	}

	return nil
}
