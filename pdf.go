package main

import (
	"fmt"
	"os"

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

	err := api.MergeCreateFile(inputPaths, outputPath, false, nil)
	if err != nil {
		return fmt.Errorf("failed to merge PDF files: %w", err)
	}
	return nil
}

// MultiStampPages applies each page of perPagePDFs onto the corresponding 1-based page of inPDFPath.
func MultiStampPages(inPDFPath string, perPagePDFs []string, outPDFPath string, pos string) error {
	wmMap := make(map[int]*model.Watermark)
	for i, pagePDF := range perPagePDFs {
		pageNum := i + 1
		desc := fmt.Sprintf("pos:%s, scale:1.0 abs, rot:0", pos)
		wm, err := api.PDFWatermark(pagePDF, desc, true, false, types.POINTS)
		if err != nil {
			return fmt.Errorf("failed to create stamp watermark for page %d: %w", pageNum, err)
		}
		wmMap[pageNum] = wm
	}

	err := api.AddWatermarksMapFile(inPDFPath, outPDFPath, wmMap, nil)
	if err != nil {
		return fmt.Errorf("failed to apply stamp map onto PDF: %w", err)
	}
	return nil
}

// AddWatermarkPDF applies a single-page watermark PDF across all pages of inPDFPath
func AddWatermarkPDF(inPDFPath string, watermarkPDFPath string, outPDFPath string, opacity float64) error {
	desc := fmt.Sprintf("pos:c, scale:1.0 abs, rot:0, op:%.2f", opacity)
	wm, err := api.PDFWatermark(watermarkPDFPath, desc, true, false, types.POINTS)
	if err != nil {
		return fmt.Errorf("failed to create watermark configuration: %w", err)
	}

	err = api.AddWatermarksFile(inPDFPath, outPDFPath, nil, wm, nil)
	if err != nil {
		return fmt.Errorf("failed to apply watermark onto PDF: %w", err)
	}
	return nil
}
