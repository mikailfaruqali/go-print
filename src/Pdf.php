<?php

declare(strict_types=1);

namespace PDF;

use Closure;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Traits\Conditionable;
use PDF\Exceptions\PdfException;
use PDF\Models\PdfTemplate;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class Pdf
{
    use Conditionable;

    public const DEFAULT_FAVICON = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3CradialGradient id='sg' cx='30%25' cy='30%25' r='70%25'%3E%3Cstop offset='0%25' stop-color='%232ecc71'/%3E%3Cstop offset='40%25' stop-color='%23249658'/%3E%3Cstop offset='100%25' stop-color='%231a6b3f'/%3E%3C/radialGradient%3E%3C/defs%3E%3Crect x='4' y='4' width='92' height='92' rx='8' fill='url(%23sg)'/%3E%3Cg fill='%23fff' transform='translate(28,28)'%3E%3Crect x='0' y='0' width='19' height='19' rx='2'/%3E%3Crect x='23' y='0' width='19' height='19' rx='2'/%3E%3Crect x='0' y='23' width='19' height='19' rx='2'/%3E%3Crect x='23' y='23' width='19' height='19' rx='2'/%3E%3C/g%3E%3C/svg%3E";

    private const BLADE_OPTIONS = [
        'contentHtml' => 'content',
        'headerHtml' => 'header',
        'footerHtml' => 'footer',
        'watermarkHtml' => 'watermark',
    ];

    private const STRING_OPTIONS = [
        'paper' => 'paper',
        'pageWidth' => 'pageWidth',
        'pageHeight' => 'pageHeight',
        'paperWidth' => 'pageWidth',
        'paperHeight' => 'pageHeight',
        'orientation' => 'orientation',
        'margin' => 'margin',
        'marginTop' => 'marginTop',
        'marginBottom' => 'marginBottom',
        'marginLeft' => 'marginLeft',
        'marginRight' => 'marginRight',
        'headerHeight' => 'headerHeight',
        'footerHeight' => 'footerHeight',
        'headerSpacing' => 'headerSpacing',
        'footerSpacing' => 'footerSpacing',
        'headerOffset' => 'headerOffset',
        'footerOffset' => 'footerOffset',
        'title' => 'title',
        'author' => 'author',
        'subject' => 'subject',
        'keywords' => 'keywords',
        'baseUrl' => 'baseUrl',
        'theme' => 'theme',
        'dir' => 'dir',
        'icon' => 'icon',
    ];

    private const FLOAT_OPTIONS = [
        'watermarkOpacity' => 'watermarkOpacity',
        'scale' => 'scale',
    ];

    private const INT_OPTIONS = [
        'pageOffset' => 'pageOffset',
        'totalOffset' => 'totalOffset',
        'timeout' => 'timeout',
    ];

    private const BOOL_OPTIONS = [
        'watermarkBehind' => 'watermarkBehind',
        'smartShrinking' => 'smartShrinking',
        'preferCssPageSize' => 'preferCssPageSize',
        'withViewer' => 'withViewer',
        'quiet' => 'quiet',
    ];

    private string $contentHtml = '';

    private ?string $headerHtml = NULL;

    private ?string $footerHtml = NULL;

    private ?string $watermarkHtml = NULL;

    private ?string $paper = NULL;

    private ?string $pageWidth = NULL;

    private ?string $pageHeight = NULL;

    private ?string $orientation = NULL;

    private ?string $margin = NULL;

    private ?string $marginTop = NULL;

    private ?string $marginBottom = NULL;

    private ?string $marginLeft = NULL;

    private ?string $marginRight = NULL;

    private ?string $headerHeight = NULL;

    private ?string $footerHeight = NULL;

    private ?string $headerSpacing = NULL;

    private ?string $footerSpacing = NULL;

    private ?string $headerOffset = NULL;

    private ?string $footerOffset = NULL;

    private ?float $watermarkOpacity = NULL;

    private ?bool $watermarkBehind = NULL;

    private ?float $scale = NULL;

    private ?int $pageOffset = NULL;

    private ?int $totalOffset = NULL;

    private ?string $title = NULL;

    private ?string $author = NULL;

    private ?string $subject = NULL;

    private ?string $keywords = NULL;

    private ?string $baseUrl = NULL;

    private bool $smartShrinking = FALSE;

    private ?bool $preferCssPageSize = NULL;

    private bool $quiet = TRUE;

    private ?int $timeout;

    private ?string $chromePath;

    private ?string $binaryPath;

    private ?string $tempDirectory;

    private bool $withViewer = FALSE;

    private ?string $fontPath = NULL;

    private ?string $fontFamily = NULL;

    private ?string $fontStack = NULL;

    private ?string $dir = NULL;

    private string $theme = 'dark';

    private ?string $icon;

    private array $cssVariables = [];

    private array $cssFiles = [];

    private array $templateOptions = [];

    private array $templateData = [];

    public function __construct()
    {
        $this->initializeDefaults();
    }

    public static function make(): self
    {
        return new self;
    }

    public function view(string $view, array $data = []): self
    {
        $options = $this->resolveTemplateOptions($view);
        $this->templateOptions = $options;
        $this->templateData = $data;

        if (blank($options['contentHtml'] ?? NULL) && function_exists('view')) {
            $this->content(view($view, $data));
        }

        $this->applyResolvedOptions($options, $data);

        return $this;
    }

    public function loadTemplate(string $view, array $data = []): self
    {
        $options = $this->resolveTemplateOptions($view);
        $this->templateOptions = $options;
        $this->templateData = $data;

        $this->applyResolvedOptions($options, $data);

        return $this;
    }

    public function applyTemplateOptions(array $options, array $data = []): self
    {
        $this->templateOptions = array_merge($this->templateOptions, $options);
        $this->templateData = array_merge($this->templateData, $data);

        $this->applyResolvedOptions($options, $data);

        return $this;
    }

    public function content(string|Renderable $html): self
    {
        $this->contentHtml = $this->renderHtml($html);

        return $this;
    }

    public function header(string|Renderable $html): self
    {
        $this->headerHtml = $this->renderHtml($html);

        return $this;
    }

    public function headerView(string $view, array $data = []): self
    {
        if (function_exists('view')) {
            $this->header(view($view, $data));
        }

        return $this;
    }

    public function footer(string|Renderable $html): self
    {
        $this->footerHtml = $this->renderHtml($html);

        return $this;
    }

    public function footerView(string $view, array $data = []): self
    {
        if (function_exists('view')) {
            $this->footer(view($view, $data));
        }

        return $this;
    }

    public function watermark(string|Renderable $html): self
    {
        $this->watermarkHtml = $this->renderHtml($html);

        return $this;
    }

    public function watermarkView(string $view, array $data = []): self
    {
        if (function_exists('view')) {
            $this->watermark(view($view, $data));
        }

        return $this;
    }

    public function paper(string $paper): self
    {
        $this->paper = $paper;

        return $this;
    }

    public function pageWidth(string $width): self
    {
        $this->pageWidth = $width;

        return $this;
    }

    public function pageHeight(string $height): self
    {
        $this->pageHeight = $height;

        return $this;
    }

    public function pageSize(string $width, string $height): self
    {
        $this->pageWidth = $width;
        $this->pageHeight = $height;

        return $this;
    }

    public function dimensions(string $width, string $height): self
    {
        return $this->pageSize($width, $height);
    }

    public function a4(): self
    {
        return $this->paper('A4');
    }

    public function a3(): self
    {
        return $this->paper('A3');
    }

    public function a5(): self
    {
        return $this->paper('A5');
    }

    public function letter(): self
    {
        return $this->paper('Letter');
    }

    public function legal(): self
    {
        return $this->paper('Legal');
    }

    public function orientation(string $orientation): self
    {
        $this->orientation = $orientation;

        return $this;
    }

    public function portrait(): self
    {
        return $this->orientation('portrait');
    }

    public function landscape(): self
    {
        return $this->orientation('landscape');
    }

    public function margin(string $margin): self
    {
        $this->margin = $margin;

        return $this;
    }

    public function marginTop(string $margin): self
    {
        $this->marginTop = $margin;

        return $this;
    }

    public function marginBottom(string $margin): self
    {
        $this->marginBottom = $margin;

        return $this;
    }

    public function marginLeft(string $margin): self
    {
        $this->marginLeft = $margin;

        return $this;
    }

    public function marginRight(string $margin): self
    {
        $this->marginRight = $margin;

        return $this;
    }

    public function headerHeight(string $height): self
    {
        $this->headerHeight = $height;

        return $this;
    }

    public function footerHeight(string $height): self
    {
        $this->footerHeight = $height;

        return $this;
    }

    public function headerSpacing(string $spacing): self
    {
        $this->headerSpacing = $spacing;

        return $this;
    }

    public function footerSpacing(string $spacing): self
    {
        $this->footerSpacing = $spacing;

        return $this;
    }

    public function headerOffset(string $offset): self
    {
        $this->headerOffset = $offset;

        return $this;
    }

    public function footerOffset(string $offset): self
    {
        $this->footerOffset = $offset;

        return $this;
    }

    public function watermarkOpacity(float $opacity): self
    {
        $this->watermarkOpacity = $opacity;

        return $this;
    }

    public function watermarkBehind(bool $behind = TRUE): self
    {
        $this->watermarkBehind = $behind;

        return $this;
    }

    public function scale(float $scale): self
    {
        $this->scale = $scale;

        return $this;
    }

    /**
     * Shrink the rendered page just enough to pull overflowing content back
     * inside the printable width, like wkhtmltopdf's smart shrinking. Disabled
     * by default; it only ever scales down, never past 50%.
     */
    public function smartShrinking(bool $smartShrinking = TRUE): self
    {
        $this->smartShrinking = $smartShrinking;

        return $this;
    }

    public function withoutSmartShrinking(): self
    {
        return $this->smartShrinking(FALSE);
    }

    public function preferCssPageSize(bool $prefer = TRUE): self
    {
        $this->preferCssPageSize = $prefer;

        return $this;
    }

    public function pageOffset(int $offset): self
    {
        $this->pageOffset = $offset;

        return $this;
    }

    public function totalOffset(int $offset): self
    {
        $this->totalOffset = $offset;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title
            ?: $this->extractTitleFromHtml($this->contentHtml)
            ?: NULL;
    }

    public function author(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function keywords(string $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function baseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function quiet(bool $quiet = TRUE): self
    {
        $this->quiet = $quiet;

        return $this;
    }

    public function timeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function chromePath(string $chromePath): self
    {
        $this->chromePath = $chromePath;

        return $this;
    }

    public function binaryPath(string $binaryPath): self
    {
        $this->binaryPath = $binaryPath;

        return $this;
    }

    public function tempDirectory(string $tempDirectory): self
    {
        $this->tempDirectory = $tempDirectory;

        return $this;
    }

    public function withViewer(bool $withViewer = TRUE): self
    {
        $this->withViewer = $withViewer;

        return $this;
    }

    public function font(?string $path = NULL, ?string $family = NULL, ?string $stack = NULL): self
    {
        $this->fontPath = $path === NULL ? NULL : $this->normalizePath($path);
        $this->fontFamily = $family;
        $this->fontStack = $stack;

        return $this;
    }

    public function dir(?string $dir = NULL): self
    {
        if ($dir === NULL) {
            $this->dir = NULL;

            return $this;
        }

        $normalized = strtolower(trim($dir));

        $this->dir = in_array($normalized, ['ltr', 'rtl', 'auto'], TRUE) ? $normalized : 'ltr';

        return $this;
    }

    public function rtl(): self
    {
        return $this->dir('rtl');
    }

    public function ltr(): self
    {
        return $this->dir('ltr');
    }

    public function theme(string $theme = 'dark'): self
    {
        $normalized = strtolower(trim($theme));

        $this->theme = in_array($normalized, ['dark', 'light', 'auto'], TRUE) ? $normalized : 'dark';

        return $this;
    }

    public function darkMode(bool $dark = TRUE): self
    {
        return $this->theme($dark ? 'dark' : 'light');
    }

    public function lightMode(bool $light = TRUE): self
    {
        return $this->theme($light ? 'light' : 'dark');
    }

    /**
     * Attach an external CSS file so its rules are inlined into the content,
     * header, footer and watermark fragments sent to the pdf binary.
     */
    public function cssFile(string $path): self
    {
        $normalized = $this->normalizePath($path);

        if (! in_array($normalized, $this->cssFiles, TRUE)) {
            $this->cssFiles[] = $normalized;
        }

        return $this;
    }

    public function cssFiles(array $paths): self
    {
        foreach ($paths as $path) {
            $this->cssFile((string) $path);
        }

        return $this;
    }

    public function withoutCssFiles(): self
    {
        $this->cssFiles = [];

        return $this;
    }

    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }

    public function icon(?string $icon = NULL): self
    {
        $this->icon = $icon === NULL || trim($icon) === '' ? NULL : trim($icon);

        return $this;
    }

    /**
     * Merge a set of CSS custom properties applied at runtime to the rendered
     * document and to the viewer. Keys may be given with or without the
     * leading double dash (e.g. 'sn-accent' or '--sn-accent').
     */
    public function cssVariables(array $variables): self
    {
        foreach ($variables as $name => $value) {
            $this->cssVariable((string) $name, $value);
        }

        return $this;
    }

    public function cssVars(array $variables): self
    {
        return $this->cssVariables($variables);
    }

    public function cssVariable(string $name, string|int|float|null $value): self
    {
        $normalized = $this->normalizeCssVariableName($name);

        if ($normalized === NULL) {
            return $this;
        }

        if ($value === NULL || trim((string) $value) === '') {
            unset($this->cssVariables[$normalized]);

            return $this;
        }

        $this->cssVariables[$normalized] = $this->normalizeCssVariableValue((string) $value);

        return $this;
    }

    public function cssVar(string $name, string|int|float|null $value): self
    {
        return $this->cssVariable($name, $value);
    }

    public function withoutCssVariables(): self
    {
        $this->cssVariables = [];

        return $this;
    }

    public function getCssVariables(): array
    {
        return $this->cssVariables;
    }

    public function get(): string
    {
        return $this->renderPdfToBuffer();
    }

    /**
     * Dump the resolved content/header/footer/watermark HTML and stop
     * execution, the same way Laravel's dd() works. Safe to drop anywhere in
     * a fluent chain regardless of the enclosing method's declared return
     * type, since dd() exits before control returns to the caller.
     */
    public function ddHtml(?string $section = NULL): void
    {
        $this->debugHtml();
    }

    /**
     * Return the final, fully-resolved HTML for each fragment (content,
     * header, footer, watermark) exactly as it would be written to the temp
     * files and handed to the pdf binary — after template options, css
     * variables, attached css files and the @font-face block are injected.
     * Useful for inspecting why a font or style isn't showing up in the
     * generated PDF.
     */
    public function debugHtml(): array
    {
        if ($this->templateOptions !== []) {
            $this->applyResolvedOptions($this->templateOptions, $this->templateData);
        }

        return [
            'content' => $this->injectCssVariables($this->contentHtml),
            'header' => $this->headerHtml === NULL ? NULL : $this->injectCssVariables($this->headerHtml),
            'footer' => $this->footerHtml === NULL ? NULL : $this->injectCssVariables($this->footerHtml),
            'watermark' => $this->watermarkHtml === NULL ? NULL : $this->injectCssVariables($this->watermarkHtml),
        ];
    }

    /**
     * Render all four fragments concatenated into one HTML page, each
     * wrapped and labelled, so you can open it directly in a browser to see
     * exactly what the pdf binary receives (fonts, css files, css variables
     * included). Pass a section name ('content', 'header', 'footer',
     * 'watermark') to inspect only that fragment.
     */
    public function dumpHtml(?string $section = NULL): Response
    {
        $fragments = $this->debugHtml();

        if ($section !== NULL) {
            $html = $fragments[$section] ?? sprintf('<p>No "%s" fragment is set.</p>', htmlspecialchars($section));

            return new Response((string) $html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        $sections = '';

        foreach ($fragments as $name => $html) {
            $sections .= sprintf(
                '<section style="border:2px dashed #888;margin:16px 0;"><h2 style="background:#222;color:#fff;padding:8px;margin:0;">%s</h2><iframe srcdoc="%s" style="width:100%%;height:600px;border:0;"></iframe></section>',
                strtoupper($name),
                $html === NULL ? '' : htmlspecialchars((string) $html, ENT_QUOTES, 'UTF-8')
            );
        }

        $page = sprintf('<!doctype html><html><head><meta charset="utf-8"><title>PDF Debug</title></head><body>%s</body></html>', $sections);

        return new Response($page, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function save(string|Closure $destination): mixed
    {
        $pdfData = $this->get();

        if ($destination instanceof Closure) {
            return $destination($pdfData, $this);
        }

        $directory = dirname($destination);

        if (! is_dir($directory) && ! mkdir($directory, 0755, TRUE) && ! is_dir($directory)) {
            throw PdfException::saveFailed($destination);
        }

        if (file_put_contents($destination, $pdfData) === FALSE) {
            throw PdfException::saveFailed($destination);
        }

        return $destination;
    }

    public function toFile(string|Closure $path): mixed
    {
        return $this->save($path);
    }

    public function download(?string $filename = NULL): Response
    {
        $formattedFilename = $this->normalizeFilename($filename);

        return new Response($this->get(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $formattedFilename),
        ]);
    }

    public function inline(?string $filename = NULL): Response
    {
        if ($this->withViewer) {
            return $this->renderViewer($filename);
        }

        return new Response($this->get(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $this->normalizeFilename($filename)),
        ]);
    }

    public function renderViewer(?string $filename = NULL): Response
    {
        $formattedFilename = $this->normalizeFilename($filename);
        $pdfBytes = $this->get();
        $fontDetails = $this->resolveViewerFontDetails();

        $title = $this->getTitle()
            ?: pathinfo($formattedFilename, PATHINFO_FILENAME);

        $html = view('pdf::pdf-viewer', [
            'font' => $fontDetails['base64'],
            'fontMime' => $this->fontMimeSubtype(),
            'fontFormat' => $this->fontFormat(),
            'fontFamily' => $fontDetails['family'],
            'fontStack' => $fontDetails['stack'],
            'filename' => $formattedFilename,
            'base64' => base64_encode($pdfBytes),
            'dir' => $this->dir ?: 'ltr',
            'theme' => $this->theme,
            'icon' => $this->resolveIconHref(),
            'title' => $title,
        ])->render();

        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function resolveBinaryPath(): string
    {
        $binaryName = PHP_OS_FAMILY === 'Windows' ? 'pdf.exe' : 'pdf';

        $candidates = [
            $this->binaryPath,
            $this->applicationPath('storage_path', 'pdf' . DIRECTORY_SEPARATOR . $binaryName),
            $this->applicationPath('base_path', 'pdf' . DIRECTORY_SEPARATOR . $binaryName),
            $this->applicationPath('base_path', $binaryName),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                return realpath($candidate) ?: $candidate;
            }
        }

        $executableFinder = new ExecutableFinder;
        $inPath = $executableFinder->find($binaryName);

        if ($inPath) {
            return $inPath;
        }

        throw PdfException::binaryNotFound($this->binaryPath ?? 'storage/pdf/pdf or system PATH');
    }

    private function applyResolvedOptions(array $options, array $data = []): void
    {
        foreach (self::BLADE_OPTIONS as $key => $method) {
            if (filled($options[$key] ?? NULL)) {
                $this->{$method}($this->renderBlade((string) $options[$key], $data));
            }
        }

        foreach (self::STRING_OPTIONS as $key => $method) {
            if (filled($options[$key] ?? NULL)) {
                $this->{$method}((string) $options[$key]);
            }
        }

        foreach (self::FLOAT_OPTIONS as $key => $method) {
            if (isset($options[$key]) && is_numeric($options[$key])) {
                $this->{$method}((float) $options[$key]);
            }
        }

        foreach (self::INT_OPTIONS as $key => $method) {
            if (isset($options[$key]) && is_numeric($options[$key])) {
                $this->{$method}((int) $options[$key]);
            }
        }

        foreach (self::BOOL_OPTIONS as $key => $method) {
            if (isset($options[$key])) {
                $this->{$method}(filter_var($options[$key], FILTER_VALIDATE_BOOLEAN));
            }
        }

        if (filled($options['cssVariables'] ?? NULL)) {
            $variables = $options['cssVariables'];

            if (is_string($variables)) {
                $variables = json_decode($variables, TRUE);
            }

            if (is_array($variables)) {
                $this->cssVariables(array_map(
                    fn ($value): string => $this->renderBlade((string) $value, $data),
                    $variables
                ));
            }
        }

        if (filled($options['cssFiles'] ?? NULL)) {
            $cssFiles = $options['cssFiles'];

            if (is_string($cssFiles)) {
                $cssFiles = json_decode($cssFiles, TRUE) ?? [$cssFiles];
            }

            if (is_array($cssFiles)) {
                $this->cssFiles(array_map(
                    fn ($value): string => $this->renderBlade((string) $value, $data),
                    $cssFiles
                ));
            }
        }

        if (filled($options['fontPath'] ?? NULL) || filled($options['fontFamily'] ?? NULL) || filled($options['fontStack'] ?? NULL)) {
            $this->font(
                filled($options['fontPath'] ?? NULL) ? (string) $options['fontPath'] : $this->fontPath,
                filled($options['fontFamily'] ?? NULL) ? (string) $options['fontFamily'] : $this->fontFamily,
                filled($options['fontStack'] ?? NULL) ? (string) $options['fontStack'] : $this->fontStack,
            );
        }
    }

    private function resolveTemplateOptions(string $view): array
    {
        try {
            return PdfTemplate::resolveOptionsForView(
                $view,
                function_exists('app') ? app()->getLocale() : 'en'
            );
        } catch (Throwable) {
            return [];
        }
    }

    private function resolveIconHref(): string
    {
        if ($this->icon === NULL) {
            return self::DEFAULT_FAVICON;
        }

        if (str_starts_with($this->icon, 'data:') || preg_match('#^https?://#i', $this->icon) === 1) {
            return $this->icon;
        }

        if (is_file($this->icon) && is_readable($this->icon)) {
            $contents = file_get_contents($this->icon);

            if ($contents !== FALSE) {
                return sprintf('data:%s;base64,%s', $this->iconMimeType(), base64_encode($contents));
            }
        }

        return sprintf(
            'data:image/svg+xml,%s',
            rawurlencode(sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="90" text-anchor="middle" x="50">%s</text></svg>',
                htmlspecialchars($this->icon, ENT_QUOTES | ENT_XML1, 'UTF-8')
            ))
        );
    }

    private function iconMimeType(): string
    {
        return match (strtolower(pathinfo((string) $this->icon, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'image/x-icon',
        };
    }

    private function normalizeFilename(?string $filename = NULL): string
    {
        if ($filename !== NULL && trim($filename) !== '') {
            $cleaned = str_replace(['"', "\r", "\n", '\\', '/'], '', trim($filename));

            if ($cleaned !== '') {
                return str_ends_with(strtolower($cleaned), '.pdf') ? $cleaned : "{$cleaned}.pdf";
            }
        }

        $title = $this->getTitle();
        $date = date('Y-m-d');

        if ($title !== NULL && trim($title) !== '') {
            $cleanTitle = str_replace(['"', "\r", "\n", '\\', '/'], '', trim($title));

            if ($cleanTitle !== '') {
                return sprintf('%s_%s.pdf', $cleanTitle, $date);
            }
        }

        return sprintf('document_%s.pdf', $date);
    }

    private function resolveViewerFontDetails(): array
    {
        $fontBase64 = NULL;

        $primaryFamily = match (TRUE) {
            $this->fontFamily !== NULL && $this->fontFamily !== '' => $this->fontFamily,
            $this->fontStack !== NULL && $this->fontStack !== '' => trim(explode(',', $this->fontStack)[0], " '\""),
            $this->fontPath !== NULL && $this->fontPath !== '' => pathinfo($this->fontPath, PATHINFO_FILENAME),
            default => 'system-ui',
        };

        $fontStack = match (TRUE) {
            $this->fontStack !== NULL && $this->fontStack !== '' => $this->fontStack,
            $primaryFamily !== 'system-ui' => sprintf("'%s', system-ui, sans-serif", $primaryFamily),
            default => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
        };

        if ($this->fontPath !== NULL && is_file($this->fontPath) && is_readable($this->fontPath)) {
            $contents = file_get_contents($this->fontPath);

            if ($contents !== FALSE) {
                $fontBase64 = base64_encode($contents);
            }
        }

        return [
            'base64' => $fontBase64,
            'family' => $primaryFamily,
            'stack' => $fontStack,
        ];
    }

    private function extractTitleFromHtml(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $title = html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return preg_replace('/[^\p{L}\p{N}\s_-]+/u', '', trim($title));
        }

        return NULL;
    }

    private function applicationPath(string $helper, string $suffix): ?string
    {
        if (! function_exists($helper)) {
            return NULL;
        }

        try {
            return $helper($suffix);
        } catch (Throwable) {
            return NULL;
        }
    }

    private function initializeDefaults(): void
    {
        $config = $this->readConfig();

        $this->binaryPath = $config['binary_path'] ?? NULL;
        $this->chromePath = $config['chrome_path'] ?? NULL;
        $this->timeout = isset($config['timeout']) ? (int) $config['timeout'] : 120;
        $this->tempDirectory = $config['temp_path'] ?? NULL;
        $this->icon = $config['icon'] ?? NULL;
    }

    private function readConfig(): array
    {
        if (! function_exists('config')) {
            return [];
        }

        try {
            return (array) config('pdf', []);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Render a stored option as a Blade string, falling back to the raw
     * markup when Blade is unavailable or the template fails to compile.
     */
    private function renderBlade(string $template, array $data): string
    {
        try {
            return Blade::render($template, $data);
        } catch (Throwable) {
            return $template;
        }
    }

    private function renderHtml(string|Renderable $html): string
    {
        return match (TRUE) {
            $html instanceof Renderable => $html->render(),
            default => $html,
        };
    }

    /**
     * Accept 'sn-accent', '--sn-accent' or 'Sn Accent' and return '--sn-accent',
     * or NULL when nothing usable remains after stripping unsafe characters.
     */
    private function normalizeCssVariableName(string $name): ?string
    {
        $trimmed = ltrim(trim($name), '-');
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $trimmed) ?? '';
        $safe = trim($safe, '-');

        return $safe === '' ? NULL : '--' . $safe;
    }

    /**
     * Strip anything that could break out of the declaration block.
     */
    private function normalizeCssVariableValue(string $value): string
    {
        $clean = str_replace(['</', '{', '}', ';'], '', $value);
        $clean = preg_replace('/[\r\n]+/', ' ', $clean) ?? '';

        return trim($clean);
    }

    /**
     * The declarations are emitted twice: once on :root and once on a
     * high-specificity :root:root:root selector, both with !important, so they
     * beat any :root block the content, header, footer or watermark defines
     * for itself regardless of where that block sits in the document.
     */
    private function cssVariablesStyleBlock(): string
    {
        if ($this->cssVariables === []) {
            return '';
        }

        $declarations = '';

        foreach ($this->cssVariables as $name => $value) {
            $declarations .= sprintf('%s:%s !important;', $name, $value);
        }

        return sprintf(
            '<style id="pdf-css-variables">:root{%1$s}:root:root:root{%1$s}</style>',
            $declarations
        );
    }

    /**
     * Inject the runtime variables, attached CSS files and the custom
     *
     * @font-face declaration as the last element of <body> (or the end of the
     * document for a bare fragment) so they are the final declarations the
     * parser sees and win the cascade against the document's own styles. This
     * runs on the content, header, footer and watermark fragments alike so the
     * custom font and attached stylesheets are available everywhere.
     */
    private function injectCssVariables(string $html): string
    {
        $style = $this->fontFaceStyleBlock() . $this->cssFilesStyleBlock() . $this->cssVariablesStyleBlock();

        if ($style === '' || $html === '') {
            return $html;
        }

        if (preg_match('/<\/body\s*>/i', $html) === 1) {
            return preg_replace('/<\/body\s*>/i', $style . '</body>', $html, 1) ?? $html;
        }

        if (preg_match('/<\/html\s*>/i', $html) === 1) {
            return preg_replace('/<\/html\s*>/i', $style . '</html>', $html, 1) ?? $html;
        }

        return $html . $style;
    }

    /**
     * Build a @font-face declaration from the configured font so it is
     * available to the content, header, footer and watermark fragments.
     */
    private function fontFaceStyleBlock(): string
    {
        if ($this->fontPath === NULL || $this->fontPath === '' || ! is_file($this->fontPath) || ! is_readable($this->fontPath)) {
            return '';
        }

        $contents = file_get_contents($this->fontPath);

        if ($contents === FALSE) {
            return '';
        }

        $family = $this->fontFamily ?: pathinfo($this->fontPath, PATHINFO_FILENAME);

        return sprintf(
            '<style id="pdf-font-face">@font-face{font-family:\'%s\';src:url(data:font/%s;base64,%s) format(\'%s\');font-weight:normal;font-style:normal;font-display:block;}</style>',
            addslashes($family),
            $this->fontMimeSubtype(),
            base64_encode($contents),
            $this->fontFormat()
        );
    }

    /**
     * Inline the contents of every attached CSS file so they apply to the
     * content, header, footer and watermark fragments.
     */
    private function cssFilesStyleBlock(): string
    {
        if ($this->cssFiles === []) {
            return '';
        }

        $css = '';

        foreach ($this->cssFiles as $cssFile) {
            if (is_file($cssFile) && is_readable($cssFile)) {
                $contents = file_get_contents($cssFile);

                if ($contents !== FALSE) {
                    $css .= $contents . "\n";
                }
            }
        }

        return $css === '' ? '' : sprintf('<style id="pdf-css-files">%s</style>', $css);
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function fontExtension(): string
    {
        return $this->fontPath === NULL
            ? 'ttf'
            : strtolower(pathinfo($this->fontPath, PATHINFO_EXTENSION));
    }

    private function fontMimeSubtype(): string
    {
        return match ($this->fontExtension()) {
            'woff2' => 'woff2',
            'woff' => 'woff',
            'otf' => 'opentype',
            default => 'truetype',
        };
    }

    private function fontFormat(): string
    {
        return match ($this->fontExtension()) {
            'woff2' => 'woff2',
            'woff' => 'woff',
            'otf' => 'opentype',
            default => 'truetype',
        };
    }

    private function renderPdfToBuffer(): string
    {
        if ($this->templateOptions !== []) {
            $this->applyResolvedOptions($this->templateOptions, $this->templateData);
        }

        $binary = $this->resolveBinaryPath();
        $tempFiles = [];
        $tempDir = $this->tempDirectory ?: sys_get_temp_dir();

        try {
            $command = [$binary];

            $hasFragments = $this->headerHtml !== NULL || $this->footerHtml !== NULL || $this->watermarkHtml !== NULL;

            if ($hasFragments) {
                $contentPath = $this->createTempHtmlFile($tempDir, 'pdf_content_', $this->injectCssVariables($this->contentHtml));
                $tempFiles[] = $contentPath;
                $command[] = '--content';
                $command[] = $contentPath;
            } else {
                $command[] = '--content';
                $command[] = '-';
            }

            $command[] = '--output';
            $command[] = '-';

            if ($this->headerHtml !== NULL) {
                $headerPath = $this->createTempHtmlFile($tempDir, 'pdf_header_', $this->injectCssVariables($this->headerHtml));
                $tempFiles[] = $headerPath;
                $command[] = '--header';
                $command[] = $headerPath;
            }

            if ($this->footerHtml !== NULL) {
                $footerPath = $this->createTempHtmlFile($tempDir, 'pdf_footer_', $this->injectCssVariables($this->footerHtml));
                $tempFiles[] = $footerPath;
                $command[] = '--footer';
                $command[] = $footerPath;
            }

            if ($this->watermarkHtml !== NULL) {
                $watermarkPath = $this->createTempHtmlFile($tempDir, 'pdf_watermark_', $this->injectCssVariables($this->watermarkHtml));
                $tempFiles[] = $watermarkPath;
                $command[] = '--watermark';
                $command[] = $watermarkPath;
            }

            if ($this->pageWidth !== NULL && $this->pageWidth !== '' && $this->pageHeight !== NULL && $this->pageHeight !== '') {
                $command[] = '--page-width';
                $command[] = $this->pageWidth;
                $command[] = '--page-height';
                $command[] = $this->pageHeight;
            } elseif ($this->paper !== NULL) {
                $command[] = '--paper';
                $command[] = $this->paper;
            }

            if ($this->orientation !== NULL) {
                $command[] = '--orientation';
                $command[] = $this->orientation;
            }

            if ($this->margin !== NULL) {
                $command[] = '--margin';
                $command[] = $this->margin;
            }

            if ($this->marginTop !== NULL && $this->marginTop !== '') {
                $command[] = '--margin-top';
                $command[] = $this->marginTop;
            }

            if ($this->marginBottom !== NULL && $this->marginBottom !== '') {
                $command[] = '--margin-bottom';
                $command[] = $this->marginBottom;
            }

            if ($this->marginLeft !== NULL && $this->marginLeft !== '') {
                $command[] = '--margin-left';
                $command[] = $this->marginLeft;
            }

            if ($this->marginRight !== NULL && $this->marginRight !== '') {
                $command[] = '--margin-right';
                $command[] = $this->marginRight;
            }

            if ($this->headerHeight !== NULL && $this->headerHeight !== '') {
                $command[] = '--header-height';
                $command[] = $this->headerHeight;
            }

            if ($this->footerHeight !== NULL && $this->footerHeight !== '') {
                $command[] = '--footer-height';
                $command[] = $this->footerHeight;
            }

            if ($this->headerSpacing !== NULL && $this->headerSpacing !== '') {
                $command[] = '--header-spacing';
                $command[] = $this->headerSpacing;
            }

            if ($this->footerSpacing !== NULL && $this->footerSpacing !== '') {
                $command[] = '--footer-spacing';
                $command[] = $this->footerSpacing;
            }

            if ($this->headerOffset !== NULL && $this->headerOffset !== '') {
                $command[] = '--header-offset';
                $command[] = $this->headerOffset;
            }

            if ($this->footerOffset !== NULL && $this->footerOffset !== '') {
                $command[] = '--footer-offset';
                $command[] = $this->footerOffset;
            }

            if ($this->watermarkOpacity !== NULL) {
                $command[] = '--watermark-opacity';
                $command[] = (string) $this->watermarkOpacity;
            }

            if ($this->watermarkBehind !== NULL && $this->watermarkBehind) {
                $command[] = '--watermark-behind';
            }

            if ($this->scale !== NULL) {
                $command[] = '--scale';
                $command[] = (string) $this->scale;
            }

            if ($this->smartShrinking) {
                $command[] = '--smart-shrinking';
            }

            if ($this->preferCssPageSize === TRUE) {
                $command[] = '--prefer-css-page-size';
            }

            if ($this->pageOffset !== NULL) {
                $command[] = '--page-offset';
                $command[] = (string) $this->pageOffset;
            }

            if ($this->totalOffset !== NULL) {
                $command[] = '--total-offset';
                $command[] = (string) $this->totalOffset;
            }

            if ($this->title !== NULL && $this->title !== '') {
                $command[] = '--title';
                $command[] = $this->title;
            }

            if ($this->author !== NULL && $this->author !== '') {
                $command[] = '--author';
                $command[] = $this->author;
            }

            if ($this->subject !== NULL && $this->subject !== '') {
                $command[] = '--subject';
                $command[] = $this->subject;
            }

            if ($this->keywords !== NULL && $this->keywords !== '') {
                $command[] = '--keywords';
                $command[] = $this->keywords;
            }

            if ($this->baseUrl !== NULL && $this->baseUrl !== '') {
                $command[] = '--base-url';
                $command[] = $this->baseUrl;
            }

            if ($this->chromePath !== NULL && $this->chromePath !== '') {
                $command[] = '--chrome';
                $command[] = $this->chromePath;
            }

            if ($this->timeout !== NULL) {
                $command[] = '--timeout';
                $command[] = (string) $this->timeout;
            }

            if ($this->quiet) {
                $command[] = '--quiet';
            }

            $process = new Process($command);
            $process->setTimeout($this->timeout === NULL ? NULL : (float) $this->timeout);

            if (! $hasFragments) {
                $process->setInput($this->injectCssVariables($this->contentHtml));
            }

            $process->run();

            if (! $process->isSuccessful()) {
                throw PdfException::executionFailed(
                    $process->getErrorOutput(),
                    (int) $process->getExitCode()
                );
            }

            $output = $process->getOutput();

            if ($output === '' || ! str_starts_with($output, '%PDF')) {
                throw PdfException::executionFailed(
                    $process->getErrorOutput() ?: 'The pdf binary returned no PDF data on stdout.',
                    (int) $process->getExitCode()
                );
            }

            return $output;
        } finally {
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        }
    }

    private function createTempHtmlFile(string $dir, string $prefix, string $content): string
    {
        $filePath = tempnam($dir, $prefix);

        if ($filePath === FALSE) {
            $filePath = $dir . DIRECTORY_SEPARATOR . uniqid($prefix, TRUE) . '.html';
        }

        if (file_put_contents($filePath, $content) === FALSE) {
            throw PdfException::saveFailed($filePath);
        }

        return $filePath;
    }
}
