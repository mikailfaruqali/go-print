package main

import (
	"fmt"
	"strconv"
	"strings"
)

// PaperSize represents standard dimensions in inches
type PaperSize struct {
	WidthInches  float64
	HeightInches float64
}

var paperSizes = map[string]PaperSize{
	"A0":        {WidthInches: 33.11, HeightInches: 46.81},
	"A1":        {WidthInches: 23.39, HeightInches: 33.11},
	"A2":        {WidthInches: 16.54, HeightInches: 23.39},
	"A3":        {WidthInches: 11.69, HeightInches: 16.54},
	"A4":        {WidthInches: 8.27, HeightInches: 11.69},
	"A5":        {WidthInches: 5.83, HeightInches: 8.27},
	"A6":        {WidthInches: 4.13, HeightInches: 5.83},
	"B4":        {WidthInches: 9.84, HeightInches: 13.90},
	"B5":        {WidthInches: 6.93, HeightInches: 9.84},
	"LETTER":    {WidthInches: 8.5, HeightInches: 11.0},
	"LEGAL":     {WidthInches: 8.5, HeightInches: 14.0},
	"TABLOID":   {WidthInches: 11.0, HeightInches: 17.0},
	"LEDGER":    {WidthInches: 17.0, HeightInches: 11.0},
	"EXECUTIVE": {WidthInches: 7.25, HeightInches: 10.5},
	"STATEMENT": {WidthInches: 5.5, HeightInches: 8.5},
}

// SupportedPaperNames lists the accepted --paper values for help/error text.
const SupportedPaperNames = "A0-A6, B4, B5, Letter, Legal, Tabloid, Ledger, Executive, Statement, or WIDTHxHEIGHT (e.g. 210mmx297mm)"

// GetPaperDimensions returns (widthInches, heightInches, error).
//
// paper accepts a named size or an explicit "WIDTHxHEIGHT" pair with units,
// e.g. "210mmx297mm" or "8.5inx11in", so uncommon stock is not a dead end.
func GetPaperDimensions(paper string, orientation string) (float64, float64, error) {
	upperPaper := strings.ToUpper(strings.TrimSpace(paper))

	var width, height float64
	if size, ok := paperSizes[upperPaper]; ok {
		width = size.WidthInches
		height = size.HeightInches
	} else if w, h, ok, err := parseCustomPaper(upperPaper); ok {
		if err != nil {
			return 0, 0, err
		}
		width, height = w, h
	} else {
		return 0, 0, fmt.Errorf("unsupported paper size: %s (supported: %s)", paper, SupportedPaperNames)
	}

	upperOrientation := strings.ToUpper(strings.TrimSpace(orientation))
	if upperOrientation == "LANDSCAPE" {
		width, height = height, width
	} else if upperOrientation != "PORTRAIT" && upperOrientation != "" {
		return 0, 0, fmt.Errorf("unsupported orientation: %s (supported: portrait, landscape)", orientation)
	}

	return width, height, nil
}

// parseCustomPaper handles explicit "WIDTHxHEIGHT" sizes such as "210mmx297mm".
// The bool reports whether the input looked like a custom size at all, so the
// caller can distinguish "malformed custom size" from "unknown name".
func parseCustomPaper(s string) (float64, float64, bool, error) {
	idx := strings.LastIndex(s, "X")
	if idx <= 0 || idx == len(s)-1 {
		return 0, 0, false, nil
	}
	wStr, hStr := s[:idx], s[idx+1:]

	w, err := ParseDimensionToInches(wStr)
	if err != nil {
		return 0, 0, true, fmt.Errorf("invalid custom paper width '%s': %w", wStr, err)
	}
	h, err := ParseDimensionToInches(hStr)
	if err != nil {
		return 0, 0, true, fmt.Errorf("invalid custom paper height '%s': %w", hStr, err)
	}
	if w <= 0 || h <= 0 {
		return 0, 0, true, fmt.Errorf("custom paper size must be positive, got '%s'", s)
	}
	return w, h, true, nil
}

// unitsToInches maps a CSS-style unit suffix to its inch conversion divisor.
var unitsToInches = []struct {
	suffix  string
	divisor float64
}{
	{"mm", 25.4},
	{"cm", 2.54},
	{"in", 1.0},
	{"pt", 72.0},
	{"px", 96.0},
	{"pc", 6.0}, // picas
}

// ParseDimensionToInches parses a dimension string with an optional unit
// (e.g. "25mm", "1in", "2.5cm", "10pt", "16px", "0") into inches.
// A bare number is interpreted as millimetres.
func ParseDimensionToInches(dimStr string) (float64, error) {
	lower := strings.ToLower(strings.TrimSpace(dimStr))
	if lower == "" {
		return 0, nil
	}

	for _, u := range unitsToInches {
		if strings.HasSuffix(lower, u.suffix) {
			valStr := strings.TrimSpace(strings.TrimSuffix(lower, u.suffix))
			val, err := strconv.ParseFloat(valStr, 64)
			if err != nil {
				return 0, fmt.Errorf("invalid dimension value '%s': %w", dimStr, err)
			}
			return val / u.divisor, nil
		}
	}

	val, err := strconv.ParseFloat(lower, 64)
	if err != nil {
		return 0, fmt.Errorf("invalid dimension value '%s' (expected a number with an optional mm/cm/in/pt/px unit)", dimStr)
	}
	return val / 25.4, nil
}

// InchesToPoints converts inches to PDF points (72 per inch).
func InchesToPoints(in float64) float64 { return in * 72.0 }

// equalFoldTrim compares two strings ignoring case and surrounding whitespace.
func equalFoldTrim(a, b string) bool {
	return strings.EqualFold(strings.TrimSpace(a), b)
}
