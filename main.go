package main

import (
	"errors"
	"flag"
	"fmt"
	"io"
	"os"
	"path/filepath"
	"regexp"
	"strconv"
	"strings"
)

// Version is stamped at build time with -ldflags "-X main.Version=v1.2.3".
var Version = "dev"

const usageText = `snpdf ` + `- HTML to PDF converter using headless Chrome

Usage:
  snpdf --content content.html --output invoice.pdf [options]
  cat page.html | snpdf --content - --output - > out.pdf

Input / output:
  --content <path>        Content HTML file, or "-" for stdin (required)
  --output <path>         Output PDF file, or "-" for stdout (required)
  --header <path>         Header HTML file, repeated on every page
  --footer <path>         Footer HTML file, repeated on every page
  --watermark <path>      Watermark HTML file, stamped on every page
  --base-url <dir>        Directory that relative asset URLs resolve against
                          (default: the content file's own directory)

Page setup:
  --paper <size>          A0-A6, B4, B5, Letter, Legal, Tabloid, Ledger,
                          Executive, Statement, or WIDTHxHEIGHT (default: A4)
  --orientation <mode>    portrait | landscape (default: portrait)
  --margin <dim>          Set all four margins at once
  --margin-top <dim>      Top margin    (default: 0)
  --margin-bottom <dim>   Bottom margin (default: 0)
  --margin-left <dim>     Left margin   (default: 0)
  --margin-right <dim>    Right margin  (default: 0)
  --header-height <dim>   Height reserved for the header (default: 0)
  --footer-height <dim>   Height reserved for the footer (default: 0)
  --header-spacing <dim>  Gap between header and content (default: 0)
  --footer-spacing <dim>  Gap between content and footer (default: 0)
  --scale <n>             Render scale, 0.1 - 2.0 (default: 1.0)
  --prefer-css-page-size  Honour @page size in CSS instead of --paper

  Dimensions accept mm, cm, in, pt, px, or a bare number (millimetres):
  25mm, 1in, 2.5cm, 18pt, 96px, 25

Watermark:
  --watermark-opacity <n> Opacity 0.0 - 1.0 (default: 0.3)
  --watermark-behind      Draw the watermark under the content instead of over

Page numbering (usable in header/footer HTML):
  {page} / {pageNumber}   Current page number
  {pages} / {totalPages}  Total page count
  {page+N} / {page-N}     Offset current page, e.g. {page+1}
  --page-offset <n>       Add n to every rendered page number (default: 0)
  --total-offset <n>      Add n to the reported total page count (default: 0)

Metadata:
  --title <text>          PDF document title
  --author <text>         PDF document author
  --subject <text>        PDF document subject
  --keywords <text>       PDF document keywords

Behaviour:
  --chrome <path>         Path to the Chrome/Chromium/Edge executable
  --timeout <seconds>     Per-page render timeout (default: 120)
  --quiet, -q             Suppress progress output
  --version, -v           Print version and exit
  --help, -h              Show this help
`

func printUsage() { fmt.Fprint(os.Stderr, usageText) }

func main() {
	if err := run(); err != nil {
		if errors.Is(err, flag.ErrHelp) {
			os.Exit(0)
		}
		fmt.Fprintf(os.Stderr, "snpdf: %v\n", err)
		os.Exit(1)
	}
}

// pageExprRe matches {page}, {pageNumber}, {pages}, {totalPages} with an
// optional +N / -N offset, e.g. {page+1} or {totalPages-1}.
var pageExprRe = regexp.MustCompile(`\{\s*(pageNumber|page|totalPages|pages)\s*([+-]\s*\d+)?\s*\}`)

// replacePagePlaceholders substitutes page-number tokens in a header/footer.
func replacePagePlaceholders(template string, pageNum, totalPages int) string {
	return pageExprRe.ReplaceAllStringFunc(template, func(match string) string {
		groups := pageExprRe.FindStringSubmatch(match)
		if groups == nil {
			return match
		}

		base := pageNum
		switch groups[1] {
		case "totalPages", "pages":
			base = totalPages
		}

		if offset := strings.ReplaceAll(groups[2], " ", ""); offset != "" {
			if delta, err := strconv.Atoi(offset); err == nil {
				base += delta
			}
		}
		return strconv.Itoa(base)
	})
}

// hasPagePlaceholder reports whether a template varies per page. When it does
// not, one render is reused for every page instead of N identical renders.
func hasPagePlaceholder(template string) bool {
	return pageExprRe.MatchString(template)
}

// config holds every resolved CLI option.
type config struct {
	contentFile, outputFile          string
	headerFile, footerFile           string
	watermarkFile, baseURL           string
	paperSize, orientation           string
	margin                           string
	marginTop, marginBottom          string
	marginLeft, marginRight          string
	headerHeight, footerHeight       string
	headerSpacing, footerSpacing     string
	scale                            float64
	preferCSSPageSize                bool
	watermarkOpacity                 float64
	watermarkBehind                  bool
	pageOffset, totalOffset          int
	title, author, subject, keywords string
	chromePath                       string
	timeoutSeconds                   int
	quiet, showHelp, showVersion     bool
}

func parseFlags(cfg *config) error {
	fs := flag.NewFlagSet("snpdf", flag.ContinueOnError)
	fs.SetOutput(io.Discard)
	fs.Usage = printUsage

	fs.StringVar(&cfg.contentFile, "content", "", "")
	fs.StringVar(&cfg.outputFile, "output", "", "")
	fs.StringVar(&cfg.headerFile, "header", "", "")
	fs.StringVar(&cfg.footerFile, "footer", "", "")
	fs.StringVar(&cfg.watermarkFile, "watermark", "", "")
	fs.StringVar(&cfg.baseURL, "base-url", "", "")
	fs.StringVar(&cfg.paperSize, "paper", "A4", "")
	fs.StringVar(&cfg.orientation, "orientation", "portrait", "")
	fs.StringVar(&cfg.margin, "margin", "", "")
	fs.StringVar(&cfg.marginTop, "margin-top", "0", "")
	fs.StringVar(&cfg.marginBottom, "margin-bottom", "0", "")
	fs.StringVar(&cfg.marginLeft, "margin-left", "0", "")
	fs.StringVar(&cfg.marginRight, "margin-right", "0", "")
	fs.StringVar(&cfg.headerHeight, "header-height", "0", "")
	fs.StringVar(&cfg.footerHeight, "footer-height", "0", "")
	fs.StringVar(&cfg.headerSpacing, "header-spacing", "0", "")
	fs.StringVar(&cfg.footerSpacing, "footer-spacing", "0", "")
	fs.Float64Var(&cfg.scale, "scale", 1.0, "")
	fs.BoolVar(&cfg.preferCSSPageSize, "prefer-css-page-size", false, "")
	fs.Float64Var(&cfg.watermarkOpacity, "watermark-opacity", 0.3, "")
	fs.BoolVar(&cfg.watermarkBehind, "watermark-behind", false, "")
	fs.IntVar(&cfg.pageOffset, "page-offset", 0, "")
	fs.IntVar(&cfg.totalOffset, "total-offset", 0, "")
	fs.StringVar(&cfg.title, "title", "", "")
	fs.StringVar(&cfg.author, "author", "", "")
	fs.StringVar(&cfg.subject, "subject", "", "")
	fs.StringVar(&cfg.keywords, "keywords", "", "")
	fs.StringVar(&cfg.chromePath, "chrome", "", "")
	fs.IntVar(&cfg.timeoutSeconds, "timeout", 120, "")
	fs.BoolVar(&cfg.quiet, "quiet", false, "")
	fs.BoolVar(&cfg.quiet, "q", false, "")
	fs.BoolVar(&cfg.showHelp, "help", false, "")
	fs.BoolVar(&cfg.showHelp, "h", false, "")
	fs.BoolVar(&cfg.showVersion, "version", false, "")
	fs.BoolVar(&cfg.showVersion, "v", false, "")

	if err := fs.Parse(os.Args[1:]); err != nil {
		if errors.Is(err, flag.ErrHelp) {
			printUsage()
			return flag.ErrHelp
		}
		printUsage()
		return err
	}
	if extra := fs.Args(); len(extra) > 0 {
		return fmt.Errorf("unexpected argument %q (all options use --flag form)", extra[0])
	}
	return nil
}

// readHTMLInput reads an HTML file, or stdin when path is "-".
func readHTMLInput(path string) (string, error) {
	if path == "-" {
		data, err := io.ReadAll(os.Stdin)
		if err != nil {
			return "", fmt.Errorf("failed to read HTML from stdin: %w", err)
		}
		if len(data) == 0 {
			return "", errors.New("no HTML received on stdin")
		}
		return string(data), nil
	}
	data, err := os.ReadFile(path)
	if err != nil {
		return "", fmt.Errorf("failed to read '%s': %w", path, err)
	}
	return string(data), nil
}

func run() error {
	var cfg config
	if err := parseFlags(&cfg); err != nil {
		return err
	}

	if cfg.showHelp {
		printUsage()
		return nil
	}
	if cfg.showVersion {
		fmt.Println("snpdf " + Version)
		return nil
	}
	if cfg.contentFile == "" {
		printUsage()
		return errors.New("--content is required")
	}
	if cfg.outputFile == "" {
		printUsage()
		return errors.New("--output is required")
	}

	toStdout := cfg.outputFile == "-"
	// Progress must never contaminate a PDF being piped to stdout.
	logf := func(format string, args ...interface{}) {
		if !cfg.quiet {
			fmt.Fprintf(os.Stderr, format, args...)
		}
	}

	contentHTML, err := readHTMLInput(cfg.contentFile)
	if err != nil {
		return err
	}

	var headerHTML, footerHTML, watermarkHTML string
	if cfg.headerFile != "" {
		if headerHTML, err = readHTMLInput(cfg.headerFile); err != nil {
			return err
		}
	}
	if cfg.footerFile != "" {
		if footerHTML, err = readHTMLInput(cfg.footerFile); err != nil {
			return err
		}
	}
	if cfg.watermarkFile != "" {
		if watermarkHTML, err = readHTMLInput(cfg.watermarkFile); err != nil {
			return err
		}
	}

	geo, err := resolveGeometry(&cfg)
	if err != nil {
		return err
	}

	if cfg.scale < 0.1 || cfg.scale > 2.0 {
		return fmt.Errorf("--scale must be between 0.1 and 2.0, got %g", cfg.scale)
	}
	if cfg.watermarkOpacity < 0 || cfg.watermarkOpacity > 1 {
		return fmt.Errorf("--watermark-opacity must be between 0.0 and 1.0, got %g", cfg.watermarkOpacity)
	}
	if cfg.timeoutSeconds <= 0 {
		return fmt.Errorf("--timeout must be positive, got %d", cfg.timeoutSeconds)
	}

	// Relative assets resolve against --base-url, else the content's directory.
	baseDir := cfg.baseURL
	if baseDir == "" && cfg.contentFile != "-" {
		if abs, err := filepath.Abs(cfg.contentFile); err == nil {
			baseDir = filepath.Dir(abs)
		}
	}
	if baseDir != "" {
		info, err := os.Stat(baseDir)
		if err != nil || !info.IsDir() {
			return fmt.Errorf("--base-url must be an existing directory: %s", baseDir)
		}
	}

	chromeBin, err := DetectChromeBinary(cfg.chromePath)
	if err != nil {
		return err
	}

	tempDir, err := os.MkdirTemp("", "snpdf-*")
	if err != nil {
		return fmt.Errorf("failed to create temp directory: %w", err)
	}
	defer os.RemoveAll(tempDir)

	renderer, err := NewChromeRenderer(chromeBin, cfg.quiet)
	if err != nil {
		return err
	}
	defer renderer.Close()

	job := &job{
		cfg:      &cfg,
		geo:      geo,
		renderer: renderer,
		tempDir:  tempDir,
		baseDir:  baseDir,
		logf:     logf,
	}

	finalPDF, totalPages, err := job.build(contentHTML, headerHTML, footerHTML, watermarkHTML)
	if err != nil {
		return err
	}

	if err := writeOutput(finalPDF, cfg.outputFile, toStdout); err != nil {
		return err
	}

	if toStdout {
		logf("Generated %d page(s) to stdout\n", totalPages)
	} else {
		logf("Generated %s (%d pages)\n", cfg.outputFile, totalPages)
	}
	return nil
}

func writeOutput(srcPDF, outputFile string, toStdout bool) error {
	data, err := os.ReadFile(srcPDF)
	if err != nil {
		return fmt.Errorf("failed to read generated PDF: %w", err)
	}

	if toStdout {
		if _, err := os.Stdout.Write(data); err != nil {
			return fmt.Errorf("failed to write PDF to stdout: %w", err)
		}
		return nil
	}

	if dir := filepath.Dir(outputFile); dir != "" && dir != "." {
		if err := os.MkdirAll(dir, 0755); err != nil {
			return fmt.Errorf("failed to create output directory '%s': %w", dir, err)
		}
	}
	if err := os.WriteFile(outputFile, data, 0644); err != nil {
		return fmt.Errorf("failed to write '%s': %w", outputFile, err)
	}
	return nil
}
