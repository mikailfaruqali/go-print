# PDF for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mikailfaruqali/pdf.svg?style=flat-square)](https://packagist.org/packages/mikailfaruqali/pdf)
[![Total Downloads](https://img.shields.io/packagist/dt/mikailfaruqali/pdf.svg?style=flat-square)](https://packagist.org/packages/mikailfaruqali/pdf)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

A modern, high-performance HTML-to-PDF converter for Laravel 11+ and PHP 8.4+, powered by headless Chrome / Chromium and the compiled Go binary `pdf`.

---

## Features

- 🚀 **Blazing Fast**: Direct headless Chrome execution via a lightweight Go core.
- 🎨 **Modern CSS**: Full support for CSS Grid, Flexbox, Tailwind CSS, SVG, custom fonts, and `@page` rules.
- 📑 **Headers, Footers & Watermarks**: Multi-fragment support with dynamic `{page}` and `{pages}` placeholders.
- 🛠️ **Developer Friendly**: Fluent, expressive API with first-class Laravel `Renderable` (Blade views) support.
- 📦 **Automated Binary Installer**: Built-in `php artisan pdf:install` to download pre-built binaries for your OS/architecture.

---

## Requirements

- PHP **8.2+**, **8.3+**, **8.4+**, **8.5+**
- Laravel **10.0+**, **11.0+**, **12.0+**, or **13.0+**
- Google Chrome, Chromium, Brave, or Microsoft Edge installed on the host system

---

## Installation

Install the package via Composer:

```bash
composer require mikailfaruqali/pdf
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=pdf-config
```

Download and install the native Go binary for your platform:

```bash
php artisan pdf:install
```

Verify your environment (checks both `pdf` binary and headless browser):

```bash
php artisan pdf:check
```

---

## Configuration

The published `config/pdf.php` file allows you to customize defaults:

```php
return [
    'binary_path' => env('PDF_BINARY_PATH'),
    'chrome_path' => env('PDF_CHROME_PATH'),
    'timeout'     => (int) env('PDF_TIMEOUT', 120),
    'temp_path'   => env('PDF_TEMP_PATH'),
];
```

---

## Basic Usage

### Using Blade Views

You can pass standard strings or any Laravel `Renderable` (such as `view()`):

```php
use PDF\Facades\Pdf;

// Download PDF as response
return Pdf::make()
    ->content(view('invoices.show', ['invoice' => $invoice]))
    ->paper('A4')
    ->download('invoice-1001.pdf');

// Display inline in browser
return Pdf::make()
    ->content(view('reports.monthly'))
    ->inline('monthly-report.pdf');

// Save directly to disk
$path = Pdf::make()
    ->content('<h1>Hello World</h1>')
    ->save(storage_path('app/exports/hello.pdf'));

// Get raw PDF bytes
$pdfBytes = Pdf::make()
    ->content('<h1>Raw Output</h1>')
    ->get();
```

---

## Advanced Options

### Headers, Footers & Page Numbers

Headers and footers support dynamic placeholders `{page}` (or `{pageNumber}`) and `{pages}` (or `{totalPages}`), as well as math expressions like `{page+1}`:

```php
return Pdf::make()
    ->content(view('documents.contract'))
    ->header(view('pdf.header'))
    ->footer('<div style="text-align: right; font-size: 10px;">Page {page} of {pages}</div>')
    ->headerHeight('25mm')
    ->footerHeight('15mm')
    ->headerSpacing('4mm')
    ->footerSpacing('4mm')
    ->download('contract.pdf');
```

### Watermark

```php
return Pdf::make()
    ->content(view('invoices.preview'))
    ->watermark('<h1 style="color: red; transform: rotate(-45deg);">CONFIDENTIAL</h1>')
    ->watermarkOpacity(0.15)
    ->watermarkBehind(true)
    ->download('confidential.pdf');
```

### Custom PDF Viewer (Font, RTL & Theme)

The viewer is **opt-in** — it is only used when `withViewer()` is called *and* you return `inline()`. Without it, `inline()` streams the raw PDF to the browser's native viewer.

> `font()`, `dir()` and `theme()` style **the viewer UI only** (its toolbar, title and chrome). They do not change the generated PDF — style the PDF from your own Blade/CSS.

```php
use PDF\Facades\Pdf;

// Default viewer (system-ui font)
return Pdf::make()
    ->content($html)
    ->withViewer()
    ->inline('invoice.pdf');

// With custom embedded font and RTL direction
return Pdf::make()
    ->content($html)
    ->font(storage_path('fonts/NotoSans-Regular.ttf'), 'Noto Sans')
    ->dir('rtl')
    ->withViewer()
    ->inline('invoice.pdf');

// Kurdish / Arabic (RTL with font and custom CSS font-stack)
return Pdf::make()
    ->content($html)
    ->font(storage_path('fonts/Rabar.ttf'), 'Rabar', "'Rabar', 'Noto Sans Arabic', sans-serif")
    ->dir('rtl')
    ->withViewer()
    ->inline('invoice.pdf');
```

#### Viewer theme (light / dark)

The viewer ships with the **sn-kit** design system (GitHub Dark / GitHub Light palettes) and defaults to dark. The theme is set server-side and can also be toggled by the user from the toolbar — their choice is remembered in `localStorage`.

```php
->theme('dark')      // default
->theme('light')
->theme('auto')      // follow the viewer's OS preference
->darkMode()         // alias for theme('dark')
->lightMode()        // alias for theme('light')
```

Supported font formats are detected from the file extension: `.ttf`, `.otf`, `.woff`, `.woff2`.

### Full Fluent API Reference

```php
Pdf::make()
    ->content(string|Renderable $html)
    ->header(string|Renderable $html)
    ->footer(string|Renderable $html)
    ->watermark(string|Renderable $html)
    ->paper('A4')                          // A0-A6, B4, B5, Letter, Legal, etc.
    ->orientation('portrait'|'landscape')
    ->margin('10mm')                       // Applies to all 4 sides
    ->marginTop('5mm')
    ->marginBottom('5mm')
    ->marginLeft('5mm')
    ->marginRight('5mm')
    ->headerHeight('25mm')
    ->footerHeight('15mm')
    ->headerSpacing('2mm')
    ->footerSpacing('2mm')
    ->headerOffset('0mm')
    ->footerOffset('0mm')
    ->watermarkOpacity(0.3)
    ->watermarkBehind(true)
    ->scale(1.0)                           // 0.1 to 2.0
    ->preferCssPageSize(true)              // Honour @page size in CSS instead of ->paper()
    ->withViewer()                         // Opt in to the built-in viewer for ->inline()
    ->font($path, $family, $stack)         // Viewer UI font
    ->dir('rtl'|'ltr')                     // Viewer UI direction (->rtl() / ->ltr())
    ->theme('dark'|'light'|'auto')         // Viewer UI theme (->darkMode() / ->lightMode())
    ->tempDirectory(sys_get_temp_dir())
    ->pageOffset(0)
    ->totalOffset(0)
    ->title('Invoice #1001')
    ->author('Mikail Faruq Ali')
    ->subject('Billing Invoice')
    ->keywords('billing, invoice, pdf')
    ->baseUrl(public_path('assets'))
    ->chromePath('/usr/bin/google-chrome')
    ->timeout(120)
    ->quiet()
    ->download('invoice.pdf')              // Return download Response
    ->inline('invoice.pdf')                // Return inline preview Response
    ->save('/path/to/invoice.pdf')         // Save to disk and return path
    ->toFile('/path/to/invoice.pdf')       // Alias for save()
    ->get();                               // Return binary PDF string
```

---

## Artisan Commands

### `php artisan pdf:install`
Detects system OS and architecture and downloads the matching `pdf` executable from GitHub Releases (`github.com/mikailfaruqali/pdf/releases/latest`) to `storage/pdf/pdf` (or `pdf.exe` on Windows), sets executable permissions, and updates `.env`.

Options:
- `--force`: Overwrite existing binary if already present.
- `--tag=latest`: Specify release tag to download.

### `php artisan pdf:check`
Validates that:
1. The `pdf` binary is installed, accessible, and executable.
2. Google Chrome / Chromium is installed and operational.

---

## License

The MIT License (MIT).
