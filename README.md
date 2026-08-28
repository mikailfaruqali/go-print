# snpdf

Standalone cross-platform CLI tool written in Go that converts HTML to PDF using headless Chrome (`chromedp`) with true separate header and footer support, dynamic page numbering (`{pageNumber}` and `{totalPages}`), and watermark overlays powered by `pdfcpu`.

## Features

- **True Separate Header & Footer**: Renders header and footer HTML documents independently and stamps them onto the content pages. This avoids the severe CSS/layout limitations of native Chrome CDP header/footer templates.
- **Dynamic Page Numbering**: Supports `{pageNumber}` and `{totalPages}` placeholders in headers and footers.
- **Watermark Support**: Overlays a dedicated watermark HTML page with configurable opacity.
- **RTL & Full Typography**: Supports Arabic, Kurdish, Hebrew, CJK, and complex CSS layouts/embedded fonts.
- **Cross-Platform**: Windows, Linux, and macOS.
- **No HTTP Server & No Docker**: Direct CLI invocation like `wkhtmltopdf`.

---

## Installation & Build

### Prerequisites
- Go 1.21+ installed
- Google Chrome, Chromium, or Microsoft Edge installed on the machine

### Build Commands

```bash
# Windows
GOOS=windows GOARCH=amd64 go build -ldflags="-s -w" -o snpdf.exe .

# Linux
GOOS=linux GOARCH=amd64 go build -ldflags="-s -w" -o snpdf .

# Linux ARM64
GOOS=linux GOARCH=arm64 go build -ldflags="-s -w" -o snpdf-arm .

# macOS (Apple Silicon)
GOOS=darwin GOARCH=arm64 go build -ldflags="-s -w" -o snpdf-darwin-arm64 .
```

---

## CLI Usage

```bash
snpdf \
  --content content.html \
  --header header.html \
  --footer footer.html \
  --output invoice.pdf \
  --paper A4 \
  --margin-top 25mm \
  --margin-bottom 25mm \
  --margin-left 10mm \
  --margin-right 10mm \
  --header-height 25mm \
  --footer-height 15mm \
  --watermark watermark.html \
  --watermark-opacity 0.3 \
  --orientation portrait \
  --chrome "C:\Program Files\Google\Chrome\Application\chrome.exe"
```

### Options & Flags

| Flag | Default | Description |
| :--- | :--- | :--- |
| `--content` | *(required)* | Path to content HTML file |
| `--output` | *(required)* | Path to destination PDF file |
| `--header` | `""` | Path to header HTML file |
| `--footer` | `""` | Path to footer HTML file |
| `--watermark` | `""` | Path to watermark HTML file |
| `--paper` | `A4` | Paper size: `A4`, `A3`, `A5`, `Letter`, `Legal` |
| `--orientation` | `portrait` | Page orientation: `portrait`, `landscape` |
| `--margin-top` | `0` | Top margin (e.g. `25mm`, `1in`, `2.5cm`, `10pt`) |
| `--margin-bottom` | `0` | Bottom margin (e.g. `25mm`, `1in`) |
| `--margin-left` | `0` | Left margin (e.g. `10mm`, `0.5in`) |
| `--margin-right` | `0` | Right margin (e.g. `10mm`, `0.5in`) |
| `--header-height` | `0` | Header area height reserved in content margins (e.g. `25mm`) |
| `--footer-height` | `0` | Footer area height reserved in content margins (e.g. `15mm`) |
| `--watermark-opacity`| `0.3` | Watermark opacity (`0.0` to `1.0`) |
| `--chrome` | `""` | Override path to Chrome/Chromium binary |
| `--help, -h` | `false` | Show usage help |

---

## Page Numbering Placeholders

You can use the following placeholders inside your `header.html` and `footer.html`:

- `{pageNumber}` or `{page}` — Current page number
- `{totalPages}` or `{pages}` — Total number of pages

Example `footer.html`:
```html
<!DOCTYPE html>
<html>
<head>
  <style>
    body {
      margin: 0;
      padding: 0 10mm;
      font-family: sans-serif;
      font-size: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: 15mm;
    }
  </style>
</head>
<body>
  <div>Company Confidential</div>
  <div>Page {pageNumber} of {totalPages}</div>
</body>
</html>
```

---

## How It Works Under The Hood

1. **Content Rendering**: Calculates total top margin (`margin-top` + `header-height`) and bottom margin (`margin-bottom` + `footer-height`), then renders `content.html` to an intermediate PDF using headless Chrome (`chromedp`).
2. **Page Counting**: Inspects the rendered PDF to determine exact total pages via `pdfcpu`.
3. **Header/Footer Generation**: For each page index (`1` to `N`), replaces `{pageNumber}` and `{totalPages}` placeholders, then renders single-page PDFs.
4. **Merging & Multi-Stamping**: Merges per-page headers/footers into unified PDFs and applies them to the corresponding pages using `pdfcpu` multi-stamping.
5. **Watermarking**: If specified, renders `watermark.html` and stamps it across all pages with custom opacity.
6. **Cleanup**: Automatically cleans up all intermediate temporary files on completion.
