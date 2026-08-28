# pdf

A standalone CLI tool that converts HTML to PDF using headless Google Chrome, Chromium, Brave, or Microsoft Edge.

- ⚡ **Near-instant startup**: written in Go, compiles to a single static binary
- 🎨 **Full modern CSS support**: Grid, Flexbox, Tailwind, Web fonts, Canvas, SVG
- 📑 **Independent headers & footers**: sit flush against the paper edge, full-bleed backgrounds supported
- 🔢 **Page numbering**: `{page}`, `{pages}`, `{pageNumber}`, `{totalPages}`, `{page+1}` placeholders
- 🏷️ **Watermarks**: stamp HTML text or images diagonally across every page with custom opacity
- 📐 **Paper sizes**: standard presets (`A4`, `Letter`, `Legal`, etc.) or custom dimensions (`210x297mm`)
- 🔒 **Zero cloud dependencies**: runs 100% locally on your machine or server

---

## Installation

### Pre-built binaries

Download the latest binary for your OS and architecture from [Releases](https://github.com/mikailfaruqali/pdf/releases).

### Build from source

Requires [Go 1.23+](https://golang.org/dl/):

```bash
# Windows
go build -ldflags="-s -w" -o pdf.exe .

# Linux
go build -ldflags="-s -w" -o pdf .

# macOS (Apple Silicon)
go build -ldflags="-s -w" -o pdf-darwin-arm64 .
```

Put the binary anywhere on your `PATH` and call it as `pdf`.

---

## Quick start

```bash
# Basic conversion
pdf \
  --content invoice.html \
  --output invoice.pdf \
  --paper A4 \
  --margin 10mm

# With header, footer, and page numbers
pdf \
  --content invoice.html \
  --output invoice.pdf \
  --header header.html \
  --footer footer.html \
  --header-height 25mm \
  --footer-height 15mm

# Pipe HTML via stdin, output PDF to stdout
cat invoice.html | pdf --content - --output - > invoice.pdf
```

---

## CLI Options

| Flag | Description | Default |
|---|---|---|
| `--content <path>` | Content HTML file, or `-` for stdin (**required**) | |
| `--output <path>` | Output PDF file, or `-` for stdout (**required**) | |
| `--header <path>` | Header HTML file, repeated on every page | |
| `--footer <path>` | Footer HTML file, repeated on every page | |
| `--watermark <path>` | Watermark HTML file, stamped on every page | |
| `--paper <size>` | `A0`–`A6`, `Letter`, `Legal`, `Tabloid`, or `WIDTHxHEIGHT` | `A4` |
| `--orientation <mode>` | `portrait` or `landscape` | `portrait` |
| `--margin <dim>` | Set all four margins at once (`10mm`, `0.5in`, `20px`) | |
| `--header-height <dim>` | Reserved header height | `0` |
| `--footer-height <dim>` | Reserved footer height | `0` |
| `--watermark-opacity <n>` | Opacity from `0.0` to `1.0` | `0.3` |
| `--watermark-behind` | Place watermark under content | `false` |
| `--scale <n>` | Render scale factor (`0.1` to `2.0`) | `1.0` |
| `--chrome <path>` | Path to browser executable | *auto-detected* |
| `--timeout <sec>` | Per-page render timeout | `120` |
| `--quiet` | Suppress progress logs | `false` |
| `--version` | Print version and exit | |

---

## License

MIT
