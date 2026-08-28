package main

import (
	"fmt"
	"os"
	"path/filepath"
	"strconv"

	"github.com/pdfcpu/pdfcpu/pkg/api"
	"github.com/pdfcpu/pdfcpu/pkg/pdfcpu/model"
	"github.com/pdfcpu/pdfcpu/pkg/pdfcpu/types"
)

// GetPDFPageCount returns the total number of pages in the given PDF file.
func GetPDFPageCount(pdfPath string) (int, error) {
	count, err := api.PageCountFile(pdfPath)
	if err != nil {
		return 0, fmt.Errorf("failed to get page count from %s: %w", pdfPath, err)
	}
	return count, nil
}

// MergePDFs merges a list of PDF files in order into a single output PDF file.
func MergePDFs(inputPaths []string, outputPath string) error {
	if len(inputPaths) == 0 {
		return fmt.Errorf("cannot merge empty list of PDF files")
	}
	if len(inputPaths) == 1 {
		input, err := os.ReadFile(inputPaths[0])
		if err != nil {
			return err
		}
		return os.WriteFile(outputPath, input, 0644)
	}

	if err := api.MergeCreateFile(inputPaths, outputPath, false, nil); err != nil {
		return fmt.Errorf("failed to merge PDF files: %w", err)
	}
	return nil
}

// StampPlacement describes where a stamp PDF is anchored on the target page.
type StampPlacement struct {
	Pos     string  // pdfcpu anchor, e.g. "tc" or "bc"
	OffsetX float64 // points, positive moves right
	OffsetY float64 // points, positive moves up
}

// MultiStampPages applies stamps to individual pages of inPDFPath.
//
// stamps maps a 1-based page number to the single-page PDF stamped onto it.
// Pages absent from the map are left untouched, which lets callers reuse one
// rendered stamp for many pages instead of re-rendering identical artwork.
func MultiStampPages(inPDFPath string, stamps map[int]string, outPDFPath string, placement StampPlacement) error {
	if len(stamps) == 0 {
		return copyFile(inPDFPath, outPDFPath)
	}

	wmMap := make(map[int]*model.Watermark, len(stamps))
	for pageNum, stampPDF := range stamps {
		// scale 1.0 abs keeps the stamp at its rendered size; offsets shift it
		// into the reserved header/footer band.
		desc := fmt.Sprintf("pos:%s, scale:1.0 abs, rot:0, off:%.2f %.2f",
			placement.Pos, placement.OffsetX, placement.OffsetY)
		wm, err := api.PDFWatermark(stampPDF, desc, true, false, types.POINTS)
		if err != nil {
			return fmt.Errorf("failed to create stamp for page %d: %w", pageNum, err)
		}
		wmMap[pageNum] = wm
	}

	if err := api.AddWatermarksMapFile(inPDFPath, outPDFPath, wmMap, nil); err != nil {
		return fmt.Errorf("failed to apply stamps onto PDF: %w", err)
	}
	return nil
}

// SplitPDFPages splits a multi-page PDF into one single-page file per page,
// written into outDir as <prefix>-1.pdf, <prefix>-2.pdf, ... It returns the
// paths keyed by 1-based page number.
func SplitPDFPages(inPDFPath, outDir, prefix string, pageCount int) (map[int]string, error) {
	out := make(map[int]string, pageCount)
	for p := 1; p <= pageCount; p++ {
		dst := filepath.Join(outDir, fmt.Sprintf("%s-%d.pdf", prefix, p))
		if err := api.TrimFile(inPDFPath, dst, []string{strconv.Itoa(p)}, nil); err != nil {
			return nil, fmt.Errorf("failed to extract page %d of %s: %w", p, prefix, err)
		}
		out[p] = dst
	}
	return out, nil
}

// AddWatermarkPDF stamps a single-page watermark PDF across every page.
//
// onTop controls whether the artwork is drawn over the content (a stamp) or
// beneath it (a true watermark).
func AddWatermarkPDF(inPDFPath string, watermarkPDFPath string, outPDFPath string, opacity float64, onTop bool) error {
	if opacity < 0 {
		opacity = 0
	}
	if opacity > 1 {
		opacity = 1
	}

	desc := fmt.Sprintf("pos:c, scale:1.0 abs, rot:0, op:%.2f", opacity)
	wm, err := api.PDFWatermark(watermarkPDFPath, desc, onTop, false, types.POINTS)
	if err != nil {
		return fmt.Errorf("failed to create watermark configuration: %w", err)
	}

	if err := api.AddWatermarksFile(inPDFPath, outPDFPath, nil, wm, nil); err != nil {
		return fmt.Errorf("failed to apply watermark onto PDF: %w", err)
	}
	return nil
}

// SetPDFMetadata writes document properties (title, author, subject, keywords)
// onto the PDF. Empty values are skipped.
func SetPDFMetadata(inPDFPath, outPDFPath string, meta map[string]string) error {
	props := make(map[string]string)
	for k, v := range meta {
		if v != "" {
			props[k] = v
		}
	}
	if len(props) == 0 {
		return copyFile(inPDFPath, outPDFPath)
	}

	if err := api.AddPropertiesFile(inPDFPath, outPDFPath, props, nil); err != nil {
		return fmt.Errorf("failed to write PDF metadata: %w", err)
	}
	return nil
}

func copyFile(src, dst string) error {
	if src == dst {
		return nil
	}
	data, err := os.ReadFile(src)
	if err != nil {
		return err
	}
	return os.WriteFile(dst, data, 0644)
}
