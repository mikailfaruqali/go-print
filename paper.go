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
	"A4":     {WidthInches: 8.27, HeightInches: 11.69},
	"A3":     {WidthInches: 11.69, HeightInches: 16.54},
	"A5":     {WidthInches: 5.83, HeightInches: 8.27},
	"LETTER": {WidthInches: 8.5, HeightInches: 11.0},
	"LEGAL":  {WidthInches: 8.5, HeightInches: 14.0},
}

// GetPaperDimensions returns (widthInches, heightInches, error)
func GetPaperDimensions(paper string, orientation string) (float64, float64, error) {
	upperPaper := strings.ToUpper(strings.TrimSpace(paper))
	size, ok := paperSizes[upperPaper]
	if !ok {
		return 0, 0, fmt.Errorf("unsupported paper size: %s (supported: A4, A3, A5, Letter, Legal)", paper)
	}

	width := size.WidthInches
	height := size.HeightInches

	upperOrientation := strings.ToUpper(strings.TrimSpace(orientation))
	if upperOrientation == "LANDSCAPE" {
		width, height = height, width
	} else if upperOrientation != "PORTRAIT" && upperOrientation != "" {
		return 0, 0, fmt.Errorf("unsupported orientation: %s (supported: portrait, landscape)", orientation)
	}

	return width, height, nil
}

// ParseDimensionToInches parses dimension string with unit (e.g. "25mm", "1in", "2.5cm", "10pt", "0") to inches.
// 1 inch = 25.4 mm
// 1 inch = 72 pt
// 1 inch = 2.54 cm
func ParseDimensionToInches(dimStr string) (float64, error) {
	dimStr = strings.TrimSpace(dimStr)
	if dimStr == "" || dimStr == "0" {
		return 0, nil
	}

	lower := strings.ToLower(dimStr)
	if strings.HasSuffix(lower, "mm") {
		valStr := strings.TrimSuffix(lower, "mm")
		val, err := strconv.ParseFloat(strings.TrimSpace(valStr), 64)
		if err != nil {
			return 0, fmt.Errorf("invalid dimension value '%s': %w", dimStr, err)
		}
		return val / 25.4, nil
	}

	if strings.HasSuffix(lower, "cm") {
		valStr := strings.TrimSuffix(lower, "cm")
		val, err := strconv.ParseFloat(strings.TrimSpace(valStr), 64)
		if err != nil {
			return 0, fmt.Errorf("invalid dimension value '%s': %w", dimStr, err)
		}
		return val / 2.54, nil
	}

	if strings.HasSuffix(lower, "in") {
		valStr := strings.TrimSuffix(lower, "in")
		val, err := strconv.ParseFloat(strings.TrimSpace(valStr), 64)
		if err != nil {
			return 0, fmt.Errorf("invalid dimension value '%s': %w", dimStr, err)
		}
		return val, nil
	}

	if strings.HasSuffix(lower, "pt") {
		valStr := strings.TrimSuffix(lower, "pt")
		val, err := strconv.ParseFloat(strings.TrimSpace(valStr), 64)
		if err != nil {
			return 0, fmt.Errorf("invalid dimension value '%s': %w", dimStr, err)
		}
		return val / 72.0, nil
	}

	if strings.HasSuffix(lower, "px") {
		valStr := strings.TrimSuffix(lower, "px")
		val, err := strconv.ParseFloat(strings.TrimSpace(valStr), 64)
		if err != nil {
			return 0, fmt.Errorf("invalid dimension value '%s': %w", dimStr, err)
		}
		return val / 96.0, nil
	}

	// If no unit provided, assume mm
	val, err := strconv.ParseFloat(lower, 64)
	if err != nil {
		return 0, fmt.Errorf("invalid dimension value '%s': %w", dimStr, err)
	}
	return val / 25.4, nil
}
