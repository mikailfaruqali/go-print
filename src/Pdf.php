<?php

declare(strict_types=1);

namespace PDF;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Response;
use Illuminate\Support\Traits\Conditionable;
use PDF\Exceptions\PdfException;
use PDF\Models\PdfTemplate;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class Pdf
{
    use Conditionable;

    private string $contentHtml = '';

    private ?string $headerHtml = NULL;

    private ?string $footerHtml = NULL;

    private ?string $watermarkHtml = NULL;

    private ?string $paper = NULL;

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

    private ?string $icon = NULL;

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
        if (function_exists('view')) {
            $this->content(view($view, $data));
        }

        $this->loadTemplate($view);

        return $this;
    }

    public function loadTemplate(string $view): self
    {
        try {
            if (class_exists(PdfTemplate::class)) {
                $locale = function_exists('app') ? app()->getLocale() : 'en';
                $options = PdfTemplate::resolveOptionsForView($view, $locale);
                if (filled($options)) {
                    $this->applyTemplateOptions($options);
                }
            }
        } catch (Throwable) {
        }

        return $this;
    }

    public function applyTemplateOptions(array $options): self
    {
        if (filled($options['contentHtml'] ?? NULL) && blank($this->contentHtml)) {
            $this->content((string) $options['contentHtml']);
        }

        if (filled($options['headerHtml'] ?? NULL) && blank($this->headerHtml)) {
            $this->header((string) $options['headerHtml']);
        }

        if (filled($options['footerHtml'] ?? NULL) && blank($this->footerHtml)) {
            $this->footer((string) $options['footerHtml']);
        }

        if (filled($options['watermarkHtml'] ?? NULL) && blank($this->watermarkHtml)) {
            $this->watermark((string) $options['watermarkHtml']);
        }

        if (filled($options['paper'] ?? NULL) && blank($this->paper)) {
            $this->paper((string) $options['paper']);
        }

        if (filled($options['orientation'] ?? NULL) && blank($this->orientation)) {
            $this->orientation((string) $options['orientation']);
        }

        if (filled($options['margin'] ?? NULL) && blank($this->margin)) {
            $this->margin((string) $options['margin']);
        }

        if (filled($options['marginTop'] ?? NULL) && blank($this->marginTop)) {
            $this->marginTop((string) $options['marginTop']);
        }

        if (filled($options['marginBottom'] ?? NULL) && blank($this->marginBottom)) {
            $this->marginBottom((string) $options['marginBottom']);
        }

        if (filled($options['marginLeft'] ?? NULL) && blank($this->marginLeft)) {
            $this->marginLeft((string) $options['marginLeft']);
        }

        if (filled($options['marginRight'] ?? NULL) && blank($this->marginRight)) {
            $this->marginRight((string) $options['marginRight']);
        }

        if (filled($options['headerHeight'] ?? NULL) && blank($this->headerHeight)) {
            $this->headerHeight((string) $options['headerHeight']);
        }

        if (filled($options['footerHeight'] ?? NULL) && blank($this->footerHeight)) {
            $this->footerHeight((string) $options['footerHeight']);
        }

        if (filled($options['headerSpacing'] ?? NULL) && blank($this->headerSpacing)) {
            $this->headerSpacing((string) $options['headerSpacing']);
        }

        if (filled($options['footerSpacing'] ?? NULL) && blank($this->footerSpacing)) {
            $this->footerSpacing((string) $options['footerSpacing']);
        }

        if (filled($options['headerOffset'] ?? NULL) && blank($this->headerOffset)) {
            $this->headerOffset((string) $options['headerOffset']);
        }

        if (filled($options['footerOffset'] ?? NULL) && blank($this->footerOffset)) {
            $this->footerOffset((string) $options['footerOffset']);
        }

        if (isset($options['watermarkOpacity']) && is_numeric($options['watermarkOpacity']) && blank($this->watermarkOpacity)) {
            $this->watermarkOpacity((float) $options['watermarkOpacity']);
        }

        if (isset($options['watermarkBehind']) && blank($this->watermarkBehind)) {
            $this->watermarkBehind(filter_var($options['watermarkBehind'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($options['scale']) && is_numeric($options['scale']) && blank($this->scale)) {
            $this->scale((float) $options['scale']);
        }

        if (isset($options['preferCssPageSize']) && blank($this->preferCssPageSize)) {
            $this->preferCssPageSize(filter_var($options['preferCssPageSize'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($options['pageOffset']) && is_numeric($options['pageOffset']) && blank($this->pageOffset)) {
            $this->pageOffset((int) $options['pageOffset']);
        }

        if (isset($options['totalOffset']) && is_numeric($options['totalOffset']) && blank($this->totalOffset)) {
            $this->totalOffset((int) $options['totalOffset']);
        }

        if (filled($options['title'] ?? NULL) && blank($this->title)) {
            $this->title((string) $options['title']);
        }

        if (filled($options['author'] ?? NULL) && blank($this->author)) {
            $this->author((string) $options['author']);
        }

        if (filled($options['subject'] ?? NULL) && blank($this->subject)) {
            $this->subject((string) $options['subject']);
        }

        if (filled($options['keywords'] ?? NULL) && blank($this->keywords)) {
            $this->keywords((string) $options['keywords']);
        }

        if (filled($options['baseUrl'] ?? NULL) && blank($this->baseUrl)) {
            $this->baseUrl((string) $options['baseUrl']);
        }

        if (isset($options['withViewer']) && ! $this->withViewer) {
            $this->withViewer(filter_var($options['withViewer'], FILTER_VALIDATE_BOOLEAN));
        }

        if (filled($options['timeout'] ?? NULL) && is_numeric($options['timeout']) && $this->timeout === 120) {
            $this->timeout((int) $options['timeout']);
        }

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

    public function icon(?string $icon = NULL): self
    {
        $this->icon = $icon === NULL || trim($icon) === '' ? NULL : trim($icon);

        return $this;
    }

    public function get(): string
    {
        return $this->renderPdfToBuffer();
    }

    public function save(string $path): string
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, TRUE) && ! is_dir($directory)) {
            throw PdfException::saveFailed($path);
        }

        $pdfData = $this->get();

        if (file_put_contents($path, $pdfData) === FALSE) {
            throw PdfException::saveFailed($path);
        }

        return $path;
    }

    public function toFile(string $path): string
    {
        return $this->save($path);
    }

    public function download(string $filename = 'document.pdf'): Response
    {
        $formattedFilename = $this->normalizeFilename($filename);

        return new Response($this->get(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $formattedFilename),
        ]);
    }

    public function inline(string $filename = 'document.pdf'): Response
    {
        if ($this->withViewer) {
            return $this->renderViewer($filename);
        }

        return new Response($this->get(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $this->normalizeFilename($filename)),
        ]);
    }

    public function renderViewer(string $filename = 'document.pdf'): Response
    {
        $formattedFilename = $this->normalizeFilename($filename);
        $pdfBytes = $this->get();
        $fontDetails = $this->resolveViewerFontDetails();

        $title = $this->title
            ?: $this->extractTitleFromHtml($this->contentHtml)
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

    private function resolveIconHref(): ?string
    {
        if ($this->icon === NULL) {
            return NULL;
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

    private function normalizeFilename(string $filename): string
    {
        $filename = str_replace(['"', "\r", "\n", '\\', '/'], '', trim($filename));

        if ($filename === '') {
            $filename = 'document.pdf';
        }

        return str_ends_with(strtolower($filename), '.pdf') ? $filename : "{$filename}.pdf";
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

    private function renderHtml(string|Renderable $html): string
    {
        return match (TRUE) {
            $html instanceof Renderable => $html->render(),
            default => $html,
        };
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
        $binary = $this->resolveBinaryPath();
        $tempFiles = [];
        $tempDir = $this->tempDirectory ?: sys_get_temp_dir();

        try {
            $command = [$binary];

            $hasFragments = $this->headerHtml !== NULL || $this->footerHtml !== NULL || $this->watermarkHtml !== NULL;

            if ($hasFragments) {
                $contentPath = $this->createTempHtmlFile($tempDir, 'pdf_content_', $this->contentHtml);
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
                $headerPath = $this->createTempHtmlFile($tempDir, 'pdf_header_', $this->headerHtml);
                $tempFiles[] = $headerPath;
                $command[] = '--header';
                $command[] = $headerPath;
            }

            if ($this->footerHtml !== NULL) {
                $footerPath = $this->createTempHtmlFile($tempDir, 'pdf_footer_', $this->footerHtml);
                $tempFiles[] = $footerPath;
                $command[] = '--footer';
                $command[] = $footerPath;
            }

            if ($this->watermarkHtml !== NULL) {
                $watermarkPath = $this->createTempHtmlFile($tempDir, 'pdf_watermark_', $this->watermarkHtml);
                $tempFiles[] = $watermarkPath;
                $command[] = '--watermark';
                $command[] = $watermarkPath;
            }

            if ($this->paper !== NULL) {
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

            if ($this->marginTop !== NULL && $this->marginTop !== '0') {
                $command[] = '--margin-top';
                $command[] = $this->marginTop;
            }

            if ($this->marginBottom !== NULL && $this->marginBottom !== '0') {
                $command[] = '--margin-bottom';
                $command[] = $this->marginBottom;
            }

            if ($this->marginLeft !== NULL && $this->marginLeft !== '0') {
                $command[] = '--margin-left';
                $command[] = $this->marginLeft;
            }

            if ($this->marginRight !== NULL && $this->marginRight !== '0') {
                $command[] = '--margin-right';
                $command[] = $this->marginRight;
            }

            if ($this->headerHeight !== NULL && $this->headerHeight !== '0') {
                $command[] = '--header-height';
                $command[] = $this->headerHeight;
            }

            if ($this->footerHeight !== NULL && $this->footerHeight !== '0') {
                $command[] = '--footer-height';
                $command[] = $this->footerHeight;
            }

            if ($this->headerSpacing !== NULL && $this->headerSpacing !== '0') {
                $command[] = '--header-spacing';
                $command[] = $this->headerSpacing;
            }

            if ($this->footerSpacing !== NULL && $this->footerSpacing !== '0') {
                $command[] = '--footer-spacing';
                $command[] = $this->footerSpacing;
            }

            if ($this->headerOffset !== NULL && $this->headerOffset !== '0') {
                $command[] = '--header-offset';
                $command[] = $this->headerOffset;
            }

            if ($this->footerOffset !== NULL && $this->footerOffset !== '0') {
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

            if ($this->preferCssPageSize === TRUE) {
                $command[] = '--prefer-css-page-size';
            }

            if ($this->pageOffset !== NULL && $this->pageOffset !== 0) {
                $command[] = '--page-offset';
                $command[] = (string) $this->pageOffset;
            }

            if ($this->totalOffset !== NULL && $this->totalOffset !== 0) {
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
                $process->setInput($this->contentHtml);
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
