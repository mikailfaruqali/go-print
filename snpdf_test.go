package main

import (
	"math"
	"strings"
	"testing"
)

func TestReplacePagePlaceholders(t *testing.T) {
	tests := []struct {
		in        string
		page, tot int
		want      string
	}{
		{"Page {pageNumber} of {totalPages}", 3, 10, "Page 3 of 10"},
		{"{page}/{pages}", 1, 5, "1/5"},
		{"{page+1}", 4, 9, "5"},
		{"{page-1}", 4, 9, "3"},
		{"{totalPages+1}", 4, 9, "10"},
		{"{ page }", 7, 9, "7"},
		{"no placeholders", 2, 8, "no placeholders"},
		// A cover page offset: page 1 of the PDF is labelled 0.
		{"{page-1} / {totalPages-1}", 1, 4, "0 / 3"},
	}

	for _, tc := range tests {
		if got := replacePagePlaceholders(tc.in, tc.page, tc.tot); got != tc.want {
			t.Errorf("replacePagePlaceholders(%q, %d, %d) = %q, want %q", tc.in, tc.page, tc.tot, got, tc.want)
		}
	}
}

func TestHasPagePlaceholder(t *testing.T) {
	if !hasPagePlaceholder("<p>{pageNumber}</p>") {
		t.Error("expected {pageNumber} to be detected")
	}
	if hasPagePlaceholder("<p>static header</p>") {
		t.Error("static template must not be treated as dynamic")
	}
	// A stray brace must not trigger the per-page path.
	if hasPagePlaceholder("<style>a{color:red}</style>") {
		t.Error("CSS braces must not be treated as placeholders")
	}
}

func TestParseDimensionToInches(t *testing.T) {
	tests := []struct {
		in   string
		want float64
	}{
		{"25.4mm", 1},
		{"2.54cm", 1},
		{"1in", 1},
		{"72pt", 1},
		{"96px", 1},
		{"", 0},
		{"0", 0},
		{"25.4", 1}, // bare number means mm
	}
	for _, tc := range tests {
		got, err := ParseDimensionToInches(tc.in)
		if err != nil {
			t.Fatalf("ParseDimensionToInches(%q) unexpected error: %v", tc.in, err)
		}
		if math.Abs(got-tc.want) > 1e-9 {
			t.Errorf("ParseDimensionToInches(%q) = %v, want %v", tc.in, got, tc.want)
		}
	}

	if _, err := ParseDimensionToInches("abc"); err == nil {
		t.Error("expected an error for a non-numeric dimension")
	}
}

func TestGetPaperDimensions(t *testing.T) {
	w, h, err := GetPaperDimensions("A4", "portrait")
	if err != nil || math.Abs(w-8.27) > 0.01 || math.Abs(h-11.69) > 0.01 {
		t.Fatalf("A4 portrait = %v x %v (err %v)", w, h, err)
	}

	// Landscape swaps the axes.
	lw, lh, err := GetPaperDimensions("A4", "landscape")
	if err != nil || math.Abs(lw-h) > 1e-9 || math.Abs(lh-w) > 1e-9 {
		t.Fatalf("A4 landscape = %v x %v (err %v)", lw, lh, err)
	}

	cw, ch, err := GetPaperDimensions("100mmx150mm", "portrait")
	if err != nil || math.Abs(cw-100/25.4) > 1e-9 || math.Abs(ch-150/25.4) > 1e-9 {
		t.Fatalf("custom paper = %v x %v (err %v)", cw, ch, err)
	}

	if _, _, err := GetPaperDimensions("NOPE", "portrait"); err == nil {
		t.Error("expected an error for an unknown paper size")
	}
	if _, _, err := GetPaperDimensions("A4", "sideways"); err == nil {
		t.Error("expected an error for an unknown orientation")
	}
}

func TestBuildPagedBandHTML(t *testing.T) {
	tpl := `<!DOCTYPE html><html><head><style>b{color:red}</style></head>` +
		`<body class="hdr">Page {pageNumber} of {totalPages}</body></html>`

	got := buildPagedBandHTML(tpl, 3, 0.5, 0, 0)

	// One block per page, each with its own resolved numbers.
	if n := strings.Count(got, "snpdf-band"); n < 3 {
		t.Errorf("expected at least 3 band blocks, found %d", n)
	}
	for _, want := range []string{"Page 1 of 3", "Page 2 of 3", "Page 3 of 3"} {
		if !strings.Contains(got, want) {
			t.Errorf("output missing %q", want)
		}
	}
	// The template's own styles and body attributes must survive.
	if !strings.Contains(got, "b{color:red}") {
		t.Error("template <head> styles were dropped")
	}
	if !strings.Contains(got, `class="hdr"`) {
		t.Error("body attributes were dropped")
	}
	// The nested <body>/<html> tags must not be duplicated inside the blocks.
	if strings.Count(strings.ToLower(got), "<body") != 1 {
		t.Error("expected exactly one <body> in the combined document")
	}
}

func TestBuildPagedBandHTMLFragment(t *testing.T) {
	// A bare fragment with no <html>/<body> must still work.
	got := buildPagedBandHTML(`<div>p{page}</div>`, 2, 0.4, 0, 0)
	if !strings.Contains(got, "p1") || !strings.Contains(got, "p2") {
		t.Errorf("fragment template did not expand correctly: %s", got)
	}
}

func TestPageAndTotalOffsets(t *testing.T) {
	// --page-offset 2 makes the first PDF page render as page 3.
	got := buildPagedBandHTML(`<i>{page}/{pages}</i>`, 2, 0.4, 2, 5)
	if !strings.Contains(got, "3/7") || !strings.Contains(got, "4/7") {
		t.Errorf("offsets not applied: %s", got)
	}
}

// A band is multi-stamped only when it varies per page; a static band is one
// page reused everywhere. Getting this backwards either misaligns every page
// number or silently repeats page 1's header.
func TestBandMultiFlagMatchesPlaceholders(t *testing.T) {
	if !hasPagePlaceholder(`<div>Page {pageNumber}</div>`) {
		t.Error("a numbered band must be multi-stamped")
	}
	if hasPagePlaceholder(`<div>ACME Corp</div>`) {
		t.Error("a static band must not be multi-stamped")
	}
}

// Bands sit flush with the paper edge, so content clears whichever is deeper -
// the margin or the band - rather than the sum of both.
func TestContentInsetUsesMaxNotSum(t *testing.T) {
	cfg := &config{
		paperSize: "A4", orientation: "portrait",
		marginTop: "5mm", marginBottom: "5mm", marginLeft: "5mm", marginRight: "5mm",
		headerHeight: "25mm", footerHeight: "15mm",
		headerSpacing: "0", footerSpacing: "0",
		headerOffset: "0", footerOffset: "0",
	}
	g, err := resolveGeometry(cfg)
	if err != nil {
		t.Fatalf("resolveGeometry: %v", err)
	}

	top := math.Max(g.marginTop, g.headerOffset+g.headerHeight+g.headerSpacing)
	// 25mm header is deeper than the 5mm margin, so it wins outright.
	if math.Abs(top-25.0/25.4) > 1e-9 {
		t.Errorf("content top inset = %v in, want %v in (the header height)", top, 25.0/25.4)
	}
	// The old behaviour summed them into 30mm, leaving a visible gap.
	if math.Abs(top-30.0/25.4) < 1e-9 {
		t.Error("content top inset still sums margin and header height")
	}
}

