package main

import (
	"fmt"
	"math"
	"os"

	"github.com/pdfcpu/pdfcpu/pkg/api"
	"github.com/pdfcpu/pdfcpu/pkg/pdfcpu"
	"github.com/pdfcpu/pdfcpu/pkg/pdfcpu/model"
	"github.com/pdfcpu/pdfcpu/pkg/pdfcpu/types"
)

// Composer applies every PDF-side operation to a single in-memory document.
//
// Each api.*File helper is a full read-validate-optimize-write cycle, so the
// old pipeline parsed and rewrote the whole PDF once per stage (header, footer,
// watermark, metadata). Loading once and writing once removes that entirely and
// is the dominant cost on long documents.
type Composer struct {
	ctx  *model.Context
	conf *model.Configuration
}

// NewComposer loads a PDF into memory ready for stamping.
func NewComposer(pdfPath string) (*Composer, error) {
	conf := model.NewDefaultConfiguration()
	conf.ValidationMode = model.ValidationRelaxed
	conf.Cmd = model.ADDWATERMARKS
	// Stamping does not need an optimized xref table, and the optimize pass is
	// a large share of load time on long documents. pdfcpu recommends skipping
	// it for exactly this case.
	conf.Optimize = false

	f, err := os.Open(pdfPath)
	if err != nil {
		return nil, fmt.Errorf("failed to open %s: %w", pdfPath, err)
	}
	defer f.Close()

	ctx, err := api.ReadValidateAndOptimize(f, conf)
	if err != nil {
		return nil, fmt.Errorf("failed to read %s: %w", pdfPath, err)
	}
	return &Composer{ctx: ctx, conf: conf}, nil
}

// PageCount returns the number of pages in the loaded document.
func (c *Composer) PageCount() int { return c.ctx.PageCount }

// StampBand draws bandPDF over the document.
//
// When bandPDF holds one page per document page, pdfcpu's native multi-stamping
// maps source page N onto destination page N in a single operation. That avoids
// splitting the band into N temporary files and building N watermark objects,
// which dominated the runtime on long documents.
//
// A single-page bandPDF repeats on every page.
func (c *Composer) StampBand(bandPDF string, placement StampPlacement, multi bool) error {
	desc := fmt.Sprintf("pos:%s, scale:1.0 abs, rot:0, off:%.2f %.2f",
		placement.Pos, placement.OffsetX, placement.OffsetY)

	wm, err := api.PDFWatermark(bandPDF, desc, true, false, types.POINTS)
	if err != nil {
		return fmt.Errorf("failed to create stamp: %w", err)
	}

	if multi {
		// PdfPageNrSrc 0 selects multi-stamp mode; source and destination both
		// start at page 1, so the mapping is 1:1. Note that PDFWatermark parses
		// the filename for ":page" suffixes, and a Windows path like C:\x.pdf
		// already lands in multi-stamp mode, so set the fields explicitly
		// rather than relying on that parsing.
		wm.PdfPageNrSrc = 0
		wm.PdfMultiStartPageNrSrc = 1
		wm.PdfMultiStartPageNrDest = 1
	} else {
		wm.PdfPageNrSrc = 1
	}

	if err := pdfcpu.AddWatermarks(c.ctx, nil, wm); err != nil {
		return fmt.Errorf("failed to apply stamp: %w", err)
	}
	return nil
}

// Watermark stamps a single-page PDF across every page.
func (c *Composer) Watermark(watermarkPDFPath string, opacity float64, onTop bool) error {
	opacity = math.Min(math.Max(opacity, 0), 1)

	desc := fmt.Sprintf("pos:c, scale:1.0 abs, rot:0, op:%.2f", opacity)
	wm, err := api.PDFWatermark(watermarkPDFPath, desc, onTop, false, types.POINTS)
	if err != nil {
		return fmt.Errorf("failed to create watermark: %w", err)
	}

	// A nil page set means every page.
	if err := pdfcpu.AddWatermarks(c.ctx, nil, wm); err != nil {
		return fmt.Errorf("failed to apply watermark: %w", err)
	}
	return nil
}

// SetMetadata writes document properties. Empty values are ignored.
func (c *Composer) SetMetadata(meta map[string]string) error {
	props := make(map[string]string, len(meta))
	for k, v := range meta {
		if v != "" {
			props[k] = v
		}
	}
	if len(props) == 0 {
		return nil
	}
	if err := pdfcpu.PropertiesAdd(c.ctx, props); err != nil {
		return fmt.Errorf("failed to write PDF metadata: %w", err)
	}
	return nil
}

// WriteFile serialises the composed document exactly once.
func (c *Composer) WriteFile(outPath string) error {
	if err := api.WriteContextFile(c.ctx, outPath); err != nil {
		return fmt.Errorf("failed to write %s: %w", outPath, err)
	}
	return nil
}

// GetPDFPageCount returns the total number of pages in the given PDF file.
func GetPDFPageCount(pdfPath string) (int, error) {
	count, err := api.PageCountFile(pdfPath)
	if err != nil {
		return 0, fmt.Errorf("failed to get page count from %s: %w", pdfPath, err)
	}
	return count, nil
}

// StampPlacement describes where a stamp PDF is anchored on the target page.
type StampPlacement struct {
	Pos     string  // pdfcpu anchor, e.g. "tc" or "bc"
	OffsetX float64 // points, positive moves right
	OffsetY float64 // points, positive moves up
}
