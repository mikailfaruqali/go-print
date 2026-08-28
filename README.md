# snpdf

A standalone, cross-platform CLI that converts HTML to PDF using headless Chrome — a modern replacement for `wkhtmltopdf`, with real separate header/footer documents, dynamic page numbering, and watermarks.

Because rendering is done by Chrome, **modern CSS just works**: flexbox, grid, custom properties, web fonts, SVG, and RTL/CJK typography.

## Features

- **True separate header & footer** — rendered as independent HTML documents and stamped onto each page, avoiding the severe CSS limits of Chrome's native print header/footer.
- **Fast on long documents** — the header and footer for the whole document are produced in a *single* Chrome render, not one per page. A 63-page report takes ~9s instead of ~91s.
- **Dynamic page numbering** — `{page}`, `{totalPages}`, and offsets like `{page+1}`.
- **Watermarks** — any HTML, with adjustable opacity, over or under the content.
- **Relative assets work** — `<img src="logo.png">` and `<link href="style.css">` resolve against the document's own directory.
- **Pipe friendly** — reads HTML on stdin and writes PDF to stdout, so no temp files are needed.
- **No HTTP server, no Docker** — a single binary invoked like `wkhtmltopdf`.

---

## Installation

### Prerequisites
- Go 1.23+ to build
- Google Chrome, Chromium, Edge, or Brave installed at runtime

### Build

```bash
# Windows
GOOS=windows GOARCH=amd64 go build -ldflags="-s -w -X main.Version=v1.0.0" -o snpdf.exe .

# Linux
GOOS=linux GOARCH=amd64 go build -ldflags="-s -w -X main.Version=v1.0.0" -o snpdf .

# macOS (Apple Silicon)
GOOS=darwin GOARCH=arm64 go build -ldflags="-s -w -X main.Version=v1.0.0" -o snpdf-darwin-arm64 .
```

Put the binary anywhere on your `PATH` and call it as `snpdf`.

---

## Usage

```bash
snpdf \
  --content content.html \
  --header header.html \
  --footer footer.html \
  --watermark watermark.html \
  --output invoice.pdf \
  --paper A4 \
  --margin 10mm \
  --header-height 25mm \
  --footer-height 15mm
```

Piping, with no files on disk:

```bash
cat invoice.html | snpdf --content - --output - > invoice.pdf
```

### Options

| Flag | Default | Description |
| :--- | :--- | :--- |
| `--content` | *(required)* | Content HTML file, or `-` for stdin |
| `--output` | *(required)* | Output PDF file, or `-` for stdout |
| `--header` | `""` | Header HTML file |
| `--footer` | `""` | Footer HTML file |
| `--watermark` | `""` | Watermark HTML file |
| `--base-url` | *content's dir* | Directory that relative asset URLs resolve against |
| `--paper` | `A4` | `A0`–`A6`, `B4`, `B5`, `Letter`, `Legal`, `Tabloid`, `Ledger`, `Executive`, `Statement`, or `WIDTHxHEIGHT` |
| `--orientation` | `portrait` | `portrait` or `landscape` |
| `--margin` | `0` | Sets all four margins at once |
| `--margin-top/-bottom/-left/-right` | `0` | Individual margins (override `--margin`) |
| `--header-height` | `0` | Height reserved for the header |
| `--footer-height` | `0` | Height reserved for the footer |
| `--header-spacing` | `0` | Gap between header and content |
| `--footer-spacing` | `0` | Gap between content and footer |
| `--header-offset` | `0` | Push the header down from the paper edge |
| `--footer-offset` | `0` | Push the footer up from the paper edge |
| `--scale` | `1.0` | Render scale, `0.1`–`2.0` |
| `--prefer-css-page-size` | `false` | Honour `@page size` in CSS instead of `--paper` |
| `--watermark-opacity` | `0.3` | Watermark opacity, `0.0`–`1.0` |
| `--watermark-behind` | `false` | Draw the watermark under the content |
| `--page-offset` | `0` | Added to every rendered page number |
| `--total-offset` | `0` | Added to the reported total page count |
| `--title` / `--author` / `--subject` / `--keywords` | `""` | PDF document metadata |
| `--chrome` | *auto* | Path to the Chrome/Chromium binary |
| `--timeout` | `120` | Per-render timeout in seconds |
| `--quiet`, `-q` | `false` | Suppress progress output |
| `--version`, `-v` | | Print version |
| `--help`, `-h` | | Show help |

**Dimensions** accept `mm`, `cm`, `in`, `pt`, `px`, or a bare number (treated as mm):
`25mm`, `1in`, `2.5cm`, `18pt`, `96px`, `25`.

### Header & footer placement

Headers and footers sit **flush against the paper edge**, the same as
`wkhtmltopdf`, so a full-bleed coloured band has no white strip above or below
it. Use `--header-offset` / `--footer-offset` to inset them.

The content area clears whichever is deeper — the page margin or the band —
rather than the sum of the two, so `--margin 5mm --header-height 25mm` leaves
25mm above the content, not 30mm.

Progress goes to **stderr** and the PDF to **stdout**, so piping is always safe.

---

## Page numbering

Use these inside `header.html` and `footer.html`:

| Placeholder | Meaning |
| :--- | :--- |
| `{page}` or `{pageNumber}` | Current page |
| `{pages}` or `{totalPages}` | Total pages |
| `{page+N}` / `{page-N}` | Offset current page, e.g. `{page+1}` |

Example `footer.html`:

```html
<!DOCTYPE html>
<html>
<head>
  <style>
    body {
      margin: 0;
      padding: 0 10mm;
      font: 12px sans-serif;
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

> Keep the header/footer content within `--header-height` / `--footer-height`. If it
> overflows onto a second page, snpdf reports the mismatch instead of silently
> misaligning the page numbers.

---

## Laravel integration

The binary is designed to be wrapped by a Laravel package. Minimal example:

```php
$process = new Symfony\Component\Process\Process([
    'snpdf',
    '--content', $contentPath,
    '--header',  $headerPath,
    '--footer',  $footerPath,
    '--output',  $outputPath,
    '--paper',   'A4',
    '--margin',  '10mm',
    '--header-height', '25mm',
    '--footer-height', '15mm',
    '--quiet',
]);
$process->mustRun();
```

Because `--content -` and `--output -` work, you can also stream a rendered Blade
view straight through the process and capture the PDF without touching disk.

---

## How it works

1. **Content** — the top/bottom margins are expanded by the reserved header and footer
   heights, then `content.html` is rendered to an intermediate PDF.
2. **Page count** — read from that PDF with `pdfcpu`.
3. **Header/footer** — if the template has no page placeholders it is rendered once and
   reused. If it does, all N variants are laid out in **one** document separated by
   forced page breaks and rendered in a single pass, then split per page.
4. **Stamping** — each band is stamped into its reserved area with `pdfcpu`.
5. **Watermark & metadata** — applied across all pages, then the result is written out.

A single Chrome process is started for the whole run and each render reuses it as a new
tab, which is what keeps large documents fast.
