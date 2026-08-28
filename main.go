package main

import (
	"flag"
	"fmt"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	"time"
)

func printUsage() {
	usage := `snpdf - HTML to PDF converter using headless Chrome and pdfcpu

Usage:
  snpdf --content content.html --output invoice.pdf [options]

Flags:
  --content          Path to content HTML file (required)
  --output           Path to output PDF file (required)
  --header           Path to header HTML file (optional)
  --footer           Path to footer HTML file (optional)
  --watermark        Path to watermark HTML file (optional)
  --paper            Paper size: A4, A3, A5, Letter, Legal (default: A4)
  --orientation      Orientation: portrait, landscape (default: portrait)
  --margin-top       Top margin (e.g. 25mm, 1in, 10pt) (default: 0)
  --margin-bottom    Bottom margin (e.g. 25mm, 1in, 10pt) (default: 0)
  --margin-left      Left margin (e.g. 10mm, 0.5in) (default: 0)
  --margin-right     Right margin (e.g. 10mm, 0.5in) (default: 0)
  --header-height    Header height (e.g. 25mm) (default: 0)
  --footer-height    Footer height (e.g. 15mm) (default: 0)
  --watermark-opacity Watermark opacity between 0.0 and 1.0 (default: 0.3)
  --chrome           Path to Chrome/Chromium executable (optional)
  --help, -h         Show help
`
	fmt.Print(usage)
}

func main() {
	if err := run(); err != nil {
		fmt.Fprintf(os.Stderr, "Error: %v\n", err)
		os.Exit(1)
	}
	os.Exit(0)
}

func replacePagePlaceholders(template string, pageNum int, totalPages int) string {
	res := strings.ReplaceAll(template, "{pageNumber}", strconv.Itoa(pageNum))
	res = strings.ReplaceAll(res, "{page}", strconv.Itoa(pageNum))
	res = strings.ReplaceAll(res, "{totalPages}", strconv.Itoa(totalPages))
	res = strings.ReplaceAll(res, "{pages}", strconv.Itoa(totalPages))
	return res
}

func run() error {
	var (
		contentFile      string
		outputFile       string
		headerFile       string
		footerFile       string
		watermarkFile    string
		paperSize        string
		orientation      string
		marginTopStr     string
		marginBottomStr  string
		marginLeftStr    string
		marginRightStr   string
		headerHeightStr  string
		footerHeightStr  string
		watermarkOpacity float64
		chromePath       string
		showHelp         bool
	)

	flag.StringVar(&contentFile, "content", "", "Path to content HTML file (required)")
	flag.StringVar(&outputFile, "output", "", "Path to output PDF file (required)")
	flag.StringVar(&headerFile, "header", "", "Path to header HTML file")
	flag.StringVar(&footerFile, "footer", "", "Path to footer HTML file")
	flag.StringVar(&watermarkFile, "watermark", "", "Path to watermark HTML file")
	flag.StringVar(&paperSize, "paper", "A4", "Paper size: A4, A3, A5, Letter, Legal")
	flag.StringVar(&orientation, "orientation", "portrait", "Orientation: portrait, landscape")
	flag.StringVar(&marginTopStr, "margin-top", "0", "Top margin (e.g. 25mm, 1in)")
	flag.StringVar(&marginBottomStr, "margin-bottom", "0", "Bottom margin (e.g. 25mm, 1in)")
	flag.StringVar(&marginLeftStr, "margin-left", "0", "Left margin (e.g. 10mm, 0.5in)")
	flag.StringVar(&marginRightStr, "margin-right", "0", "Right margin (e.g. 10mm, 0.5in)")
	flag.StringVar(&headerHeightStr, "header-height", "0", "Header height (e.g. 25mm)")
	flag.StringVar(&footerHeightStr, "footer-height", "0", "Footer height (e.g. 15mm)")
	flag.Float64Var(&watermarkOpacity, "watermark-opacity", 0.3, "Watermark opacity (0.0 - 1.0)")
	flag.StringVar(&chromePath, "chrome", "", "Path to Chrome/Chromium executable")
	flag.BoolVar(&showHelp, "help", false, "Show help")
	flag.BoolVar(&showHelp, "h", false, "Show help")

	flag.Parse()

	if showHelp {
		printUsage()
		return nil
	}

	if contentFile == "" {
		printUsage()
		return fmt.Errorf("flag --content is required")
	}
	if outputFile == "" {
		printUsage()
		return fmt.Errorf("flag --output is required")
	}

	// Read content HTML
	contentBytes, err := os.ReadFile(contentFile)
	if err != nil {
		return fmt.Errorf("failed to read content file '%s': %w", contentFile, err)
	}
	contentHTML := string(contentBytes)

	// Read optional HTML files
	var headerHTML, footerHTML, watermarkHTML string
	if headerFile != "" {
		hb, err := os.ReadFile(headerFile)
		if err != nil {
			return fmt.Errorf("failed to read header file '%s': %w", headerFile, err)
		}
		headerHTML = string(hb)
	}

	if footerFile != "" {
		fb, err := os.ReadFile(footerFile)
		if err != nil {
			return fmt.Errorf("failed to read footer file '%s': %w", footerFile, err)
		}
		footerHTML = string(fb)
	}

	if watermarkFile != "" {
		wb, err := os.ReadFile(watermarkFile)
		if err != nil {
			return fmt.Errorf("failed to read watermark file '%s': %w", watermarkFile, err)
		}
		watermarkHTML = string(wb)
	}

	// Parse dimensions
	paperWidthInches, paperHeightInches, err := GetPaperDimensions(paperSize, orientation)
	if err != nil {
		return err
	}

	marginTopInches, err := ParseDimensionToInches(marginTopStr)
	if err != nil {
		return fmt.Errorf("invalid margin-top: %w", err)
	}
	marginBottomInches, err := ParseDimensionToInches(marginBottomStr)
	if err != nil {
		return fmt.Errorf("invalid margin-bottom: %w", err)
	}
	marginLeftInches, err := ParseDimensionToInches(marginLeftStr)
	if err != nil {
		return fmt.Errorf("invalid margin-left: %w", err)
	}
	marginRightInches, err := ParseDimensionToInches(marginRightStr)
	if err != nil {
		return fmt.Errorf("invalid margin-right: %w", err)
	}

	headerHeightInches, err := ParseDimensionToInches(headerHeightStr)
	if err != nil {
		return fmt.Errorf("invalid header-height: %w", err)
	}
	footerHeightInches, err := ParseDimensionToInches(footerHeightStr)
	if err != nil {
		return fmt.Errorf("invalid footer-height: %w", err)
	}

	// Detect Chrome
	resolvedChromePath, err := DetectChromeBinary(chromePath)
	if err != nil {
		return err
	}

	// Create temp directory for intermediate PDFs
	tempDir, err := os.MkdirTemp(os.TempDir(), fmt.Sprintf("snpdf_%d_*", time.Now().UnixNano()))
	if err != nil {
		return fmt.Errorf("failed to create temp directory: %w", err)
	}
	defer os.RemoveAll(tempDir)

	// Initialize Chrome renderer
	renderer, err := NewChromeRenderer(resolvedChromePath)
	if err != nil {
		return fmt.Errorf("failed to initialize Chrome renderer: %w", err)
	}
	defer renderer.Close()

	isLandscape := strings.EqualFold(strings.TrimSpace(orientation), "landscape")

	// Step 2: Render content.html to content.pdf
	// Top/bottom margins for content = header-height + margin-top and footer-height + margin-bottom
	contentMarginTop := marginTopInches
	if headerFile != "" {
		contentMarginTop += headerHeightInches
	}
	contentMarginBottom := marginBottomInches
	if footerFile != "" {
		contentMarginBottom += footerHeightInches
	}

	contentPDFPath := filepath.Join(tempDir, "content.pdf")
	fmt.Print("Rendering content... ")
	err = renderer.RenderHTMLToPDF(contentHTML, contentPDFPath, RenderOptions{
		PaperWidthInches:   paperWidthInches,
		PaperHeightInches:  paperHeightInches,
		MarginTopInches:    contentMarginTop,
		MarginBottomInches: contentMarginBottom,
		MarginLeftInches:   marginLeftInches,
		MarginRightInches:  marginRightInches,
		Landscape:          isLandscape,
	})
	if err != nil {
		fmt.Println("failed")
		return fmt.Errorf("failed to render content PDF: %w", err)
	}
	fmt.Println("done")

	// Step 3: Get total page count from content.pdf
	totalPages, err := GetPDFPageCount(contentPDFPath)
	if err != nil {
		return fmt.Errorf("failed to inspect total pages: %w", err)
	}

	currentWorkingPDF := contentPDFPath

	// Step 4-7: Per-page Header generation and stamping
	if headerFile != "" {
		var headerPagePDFs []string
		for pageNum := 1; pageNum <= totalPages; pageNum++ {
			fmt.Printf("Page %d/%d header... ", pageNum, totalPages)
			pageHeaderHTML := replacePagePlaceholders(headerHTML, pageNum, totalPages)
			pageHeaderPDF := filepath.Join(tempDir, fmt.Sprintf("header-%d.pdf", pageNum))

			hHeight := headerHeightInches
			if hHeight <= 0 {
				hHeight = 1.0 // fallback
			}
			err := renderer.RenderHTMLToPDF(pageHeaderHTML, pageHeaderPDF, RenderOptions{
				PaperWidthInches:   paperWidthInches,
				PaperHeightInches:  hHeight,
				MarginTopInches:    0,
				MarginBottomInches: 0,
				MarginLeftInches:   0,
				MarginRightInches:  0,
				Landscape:          isLandscape,
			})
			if err != nil {
				fmt.Println("failed")
				return fmt.Errorf("failed to render header for page %d: %w", pageNum, err)
			}
			headerPagePDFs = append(headerPagePDFs, pageHeaderPDF)
			fmt.Println("done")
		}

		stampedWithHeaderPDF := filepath.Join(tempDir, "content-with-header.pdf")
		if err := MultiStampPages(currentWorkingPDF, headerPagePDFs, stampedWithHeaderPDF, "tc"); err != nil {
			return fmt.Errorf("failed to stamp headers onto content: %w", err)
		}
		currentWorkingPDF = stampedWithHeaderPDF
	}

	// Step 4-8: Per-page Footer generation and stamping
	if footerFile != "" {
		var footerPagePDFs []string
		for pageNum := 1; pageNum <= totalPages; pageNum++ {
			fmt.Printf("Page %d/%d footer... ", pageNum, totalPages)
			pageFooterHTML := replacePagePlaceholders(footerHTML, pageNum, totalPages)
			pageFooterPDF := filepath.Join(tempDir, fmt.Sprintf("footer-%d.pdf", pageNum))

			fHeight := footerHeightInches
			if fHeight <= 0 {
				fHeight = 0.6 // fallback
			}
			err := renderer.RenderHTMLToPDF(pageFooterHTML, pageFooterPDF, RenderOptions{
				PaperWidthInches:   paperWidthInches,
				PaperHeightInches:  fHeight,
				MarginTopInches:    0,
				MarginBottomInches: 0,
				MarginLeftInches:   0,
				MarginRightInches:  0,
				Landscape:          isLandscape,
			})
			if err != nil {
				fmt.Println("failed")
				return fmt.Errorf("failed to render footer for page %d: %w", pageNum, err)
			}
			footerPagePDFs = append(footerPagePDFs, pageFooterPDF)
			fmt.Println("done")
		}

		stampedWithFooterPDF := filepath.Join(tempDir, "content-with-footer.pdf")
		if err := MultiStampPages(currentWorkingPDF, footerPagePDFs, stampedWithFooterPDF, "bc"); err != nil {
			return fmt.Errorf("failed to stamp footers onto content: %w", err)
		}
		currentWorkingPDF = stampedWithFooterPDF
	}

	// Step 9: Watermark
	if watermarkFile != "" {
		fmt.Print("Rendering watermark... ")
		watermarkPDF := filepath.Join(tempDir, "watermark.pdf")
		err := renderer.RenderHTMLToPDF(watermarkHTML, watermarkPDF, RenderOptions{
			PaperWidthInches:   paperWidthInches,
			PaperHeightInches:  paperHeightInches,
			MarginTopInches:    0,
			MarginBottomInches: 0,
			MarginLeftInches:   0,
			MarginRightInches:  0,
			Landscape:          isLandscape,
		})
		if err != nil {
			fmt.Println("failed")
			return fmt.Errorf("failed to render watermark: %w", err)
		}
		fmt.Println("done")

		stampedWithWatermarkPDF := filepath.Join(tempDir, "content-with-watermark.pdf")
		if err := AddWatermarkPDF(currentWorkingPDF, watermarkPDF, stampedWithWatermarkPDF, watermarkOpacity); err != nil {
			return fmt.Errorf("failed to stamp watermark onto PDF: %w", err)
		}
		currentWorkingPDF = stampedWithWatermarkPDF
	}

	// Step 10: Copy final output to destination
	outDir := filepath.Dir(outputFile)
	if outDir != "" && outDir != "." {
		if err := os.MkdirAll(outDir, 0755); err != nil {
			return fmt.Errorf("failed to create output directory '%s': %w", outDir, err)
		}
	}

	finalBytes, err := os.ReadFile(currentWorkingPDF)
	if err != nil {
		return fmt.Errorf("failed to read processed PDF: %w", err)
	}

	if err := os.WriteFile(outputFile, finalBytes, 0644); err != nil {
		return fmt.Errorf("failed to write output PDF '%s': %w", outputFile, err)
	}

	fmt.Printf("Successfully generated %s (%d pages)\n", outputFile, totalPages)
	return nil
}
