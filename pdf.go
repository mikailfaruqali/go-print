package main

import (
	"bytes"
	"fmt"
	"io"
	"math"
	"os"

	"github.com/pdfcpu/pdfcpu/pkg/api"
	"github.com/pdfcpu/pdfcpu/pkg/pdfcpu"
	"github.com/pdfcpu/pdfcpu/pkg/pdfcpu/model"
	"github.com/pdfcpu/pdfcpu/pkg/pdfcpu/types"
)

// Composer applies every PDF-side operation to a single in-memory document.
type Composer struct {
	ctx  *model.Context
	conf *model.Configuration
}

func defaultConf() *model.Configuration {
	conf := model.NewDefaultConfiguration()
	conf.ValidationMode = model.ValidationRelaxed
	conf.Cmd = model.ADDWATERMARKS
	conf.Optimize = false
	return conf
}

// NewComposerFromBytes loads a PDF from an in-memory byte slice.
func NewComposerFromBytes(data []byte) (*Composer, error) {
	conf := defaultConf()
	ctx, err := api.ReadContext(bytes.NewReader(data), conf)
	if err != nil {
		return nil, fmt.Errorf("failed to parse PDF: %w", err)
	}
	if err := ctx.EnsurePageCount(); err != nil {
		return nil, fmt.Errorf("failed to count PDF pages: %w", err)
	}
	return &Composer{ctx: ctx, conf: conf}, nil
}

// NewComposer loads a PDF into memory ready for stamping.
func NewComposer(pdfPath string) (*Composer, error) {
	f, err := os.Open(pdfPath)
	if err != nil {
		return nil, fmt.Errorf("failed to open %s: %w", pdfPath, err)
	}
	defer f.Close()

	conf := defaultConf()
	ctx, err := api.ReadContext(f, conf)
	if err != nil {
		return nil, fmt.Errorf("failed to read %s: %w", pdfPath, err)
	}
	if err := ctx.EnsurePageCount(); err != nil {
		return nil, fmt.Errorf("failed to count pages of %s: %w", pdfPath, err)
	}
	return &Composer{ctx: ctx, conf: conf}, nil
}

// PageCount returns the number of pages in the loaded document.
func (c *Composer) PageCount() int { return c.ctx.PageCount }

// StampBandBytes draws bandBytes over the document.
func (c *Composer) StampBandBytes(bandBytes []byte, placement StampPlacement, multi bool) error {
	desc := fmt.Sprintf("pos:%s, scale:1.0 abs, rot:0, off:%.2f %.2f",
		placement.Pos, placement.OffsetX, placement.OffsetY)

	wm, err := api.PDFWatermark("band.pdf", desc, true, false, types.POINTS)
	if err != nil {
		return fmt.Errorf("failed to create stamp: %w", err)
	}
	wm.PDF = bytes.NewReader(bandBytes)

	if multi {
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

// StampBand draws bandPDF over the document.
func (c *Composer) StampBand(bandPDF string, placement StampPlacement, multi bool) error {
	data, err := os.ReadFile(bandPDF)
	if err != nil {
		return fmt.Errorf("failed to read band PDF %s: %w", bandPDF, err)
	}
	return c.StampBandBytes(data, placement, multi)
}

// WatermarkBytes stamps a single-page PDF byte slice across every page.
func (c *Composer) WatermarkBytes(watermarkBytes []byte, opacity float64, onTop bool) error {
	opacity = math.Min(math.Max(opacity, 0), 1)

	desc := fmt.Sprintf("pos:c, scale:1.0 abs, rot:0, op:%.2f", opacity)
	wm, err := api.PDFWatermark("watermark.pdf", desc, onTop, false, types.POINTS)
	if err != nil {
		return fmt.Errorf("failed to create watermark: %w", err)
	}
	wm.PDF = bytes.NewReader(watermarkBytes)

	if err := pdfcpu.AddWatermarks(c.ctx, nil, wm); err != nil {
		return fmt.Errorf("failed to apply watermark: %w", err)
	}
	return nil
}

// Watermark stamps a single-page PDF across every page.
func (c *Composer) Watermark(watermarkPDFPath string, opacity float64, onTop bool) error {
	data, err := os.ReadFile(watermarkPDFPath)
	if err != nil {
		return fmt.Errorf("failed to read watermark PDF %s: %w", watermarkPDFPath, err)
	}
	return c.WatermarkBytes(data, opacity, onTop)
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

// Write serialises the composed document to an io.Writer.
func (c *Composer) Write(w io.Writer) error {
	if err := api.WriteContext(c.ctx, w); err != nil {
		return fmt.Errorf("failed to write PDF: %w", err)
	}
	return nil
}

// WriteFile serialises the composed document exactly once to a file.
func (c *Composer) WriteFile(outPath string) error {
	if err := api.WriteContextFile(c.ctx, outPath); err != nil {
		return fmt.Errorf("failed to write %s: %w", outPath, err)
	}
	return nil
}

// GetPDFPageCountFromBytes returns total page count from a PDF byte slice.
func GetPDFPageCountFromBytes(data []byte) (int, error) {
	conf := defaultConf()
	ctx, err := api.ReadContext(bytes.NewReader(data), conf)
	if err != nil {
		return 0, fmt.Errorf("failed to parse PDF: %w", err)
	}
	if err := ctx.EnsurePageCount(); err != nil {
		return 0, fmt.Errorf("failed to count pages: %w", err)
	}
	return ctx.PageCount, nil
}

// GetPDFPageCount returns the total number of pages in the given PDF file.
func GetPDFPageCount(pdfPath string) (int, error) {
	f, err := os.Open(pdfPath)
	if err != nil {
		return 0, fmt.Errorf("failed to open %s: %w", pdfPath, err)
	}
	defer f.Close()

	conf := defaultConf()
	ctx, err := api.ReadContext(f, conf)
	if err != nil {
		return 0, fmt.Errorf("failed to read %s: %w", pdfPath, err)
	}
	if err := ctx.EnsurePageCount(); err != nil {
		return 0, fmt.Errorf("failed to count pages: %w", err)
	}
	return ctx.PageCount, nil
}

// StampPlacement describes where a stamp PDF is anchored on the target page.
type StampPlacement struct {
	Pos     string  // pdfcpu anchor, e.g. "tc" or "bc"
	OffsetX float64 // points, positive moves right
	OffsetY float64 // points, positive moves up
}
