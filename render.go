package main

import (
	"fmt"
	"math"
	"path/filepath"
	"regexp"
	"strings"
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
	logf     func(string, ...interface{})
}

func (j *job) timeout() time.Duration {
	return time.Duration(j.cfg.timeoutSeconds) * time.Second
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

	j.logf("Rendering content... ")
	contentPDF := filepath.Join(j.tempDir, "content.pdf")
	err := j.renderer.RenderHTMLToPDF(contentHTML, contentPDF, RenderOptions{
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
	if err != nil {
		j.logf("failed\n")
		return "", 0, fmt.Errorf("failed to render content: %w", err)
	}
	j.logf("done\n")

	totalPages, err := GetPDFPageCount(contentPDF)
	if err != nil {
		return "", 0, err
	}
	current := contentPDF

	if headerHTML != "" {
		current, err = j.applyBand(current, headerHTML, totalPages, bandSpec{
			name:   "header",
			height: g.headerHeight,
			// Flush with the top edge of the paper, like wkhtmltopdf, so a
			// full-bleed header band has no white strip above it. --header-offset
			// pushes it down for designs that want to sit inside the margin.
			placement: StampPlacement{Pos: "tc", OffsetY: -InchesToPoints(g.headerOffset)},
			fallback:  1.0,
		})
		if err != nil {
			return "", 0, err
		}
	}

	if footerHTML != "" {
		current, err = j.applyBand(current, footerHTML, totalPages, bandSpec{
			name:      "footer",
			height:    g.footerHeight,
			placement: StampPlacement{Pos: "bc", OffsetY: InchesToPoints(g.footerOffset)},
			fallback:  0.6,
		})
		if err != nil {
			return "", 0, err
		}
	}

	if watermarkHTML != "" {
		j.logf("Rendering watermark... ")
		wmPDF := filepath.Join(j.tempDir, "watermark.pdf")
		err := j.renderer.RenderHTMLToPDF(watermarkHTML, wmPDF, RenderOptions{
			PaperWidthInches:  g.paperWidth,
			PaperHeightInches: g.paperHeight,
			Landscape:         g.landscape,
			Scale:             1.0,
			BaseDir:           j.baseDir,
			Timeout:           j.timeout(),
		})
		if err != nil {
			j.logf("failed\n")
			return "", 0, fmt.Errorf("failed to render watermark: %w", err)
		}
		j.logf("done\n")

		out := filepath.Join(j.tempDir, "with-watermark.pdf")
		onTop := !j.cfg.watermarkBehind
		if err := AddWatermarkPDF(current, wmPDF, out, j.cfg.watermarkOpacity, onTop); err != nil {
			return "", 0, err
		}
		current = out
	}

	if meta := j.metadata(); len(meta) > 0 {
		out := filepath.Join(j.tempDir, "with-metadata.pdf")
		if err := SetPDFMetadata(current, out, meta); err != nil {
			return "", 0, err
		}
		current = out
	}

	return current, totalPages, nil
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

// applyBand renders a header or footer and stamps it onto every page.
//
// Both the static and per-page cases cost exactly one Chrome render. A template
// with page numbers is expanded into a single document holding one band per
// page, separated by forced page breaks; Chrome paginates it in one pass and
// the result is split back into per-page stamps. Rendering each page
// separately is orders of magnitude slower for long documents.
func (j *job) applyBand(inPDF, templateHTML string, totalPages int, spec bandSpec) (string, error) {
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

	stamps := make(map[int]string, totalPages)

	if !hasPagePlaceholder(templateHTML) {
		j.logf("Rendering %s (1 render, reused on %d pages)... ", spec.name, totalPages)
		path := filepath.Join(j.tempDir, spec.name+".pdf")
		if err := j.renderer.RenderHTMLToPDF(templateHTML, path, renderOpts); err != nil {
			j.logf("failed\n")
			return "", fmt.Errorf("failed to render %s: %w", spec.name, err)
		}
		for p := 1; p <= totalPages; p++ {
			stamps[p] = path
		}
		j.logf("done\n")
	} else {
		j.logf("Rendering %s (1 render for %d pages)... ", spec.name, totalPages)
		combined := buildPagedBandHTML(templateHTML, totalPages, height, j.cfg.pageOffset, j.cfg.totalOffset)
		multiPath := filepath.Join(j.tempDir, spec.name+"-all.pdf")
		if err := j.renderer.RenderHTMLToPDF(combined, multiPath, renderOpts); err != nil {
			j.logf("failed\n")
			return "", fmt.Errorf("failed to render %s: %w", spec.name, err)
		}

		got, err := GetPDFPageCount(multiPath)
		if err != nil {
			return "", err
		}
		if got != totalPages {
			// Content taller than the band can push a block onto an extra page.
			return "", fmt.Errorf("%s produced %d pages for a %d page document; reduce the %s content or increase --%s-height",
				spec.name, got, totalPages, spec.name, spec.name)
		}

		stamps, err = SplitPDFPages(multiPath, j.tempDir, spec.name, totalPages)
		if err != nil {
			return "", err
		}
		j.logf("done\n")
	}

	out := filepath.Join(j.tempDir, "with-"+spec.name+".pdf")
	if err := MultiStampPages(inPDF, stamps, out, spec.placement); err != nil {
		return "", err
	}
	return out, nil
}
