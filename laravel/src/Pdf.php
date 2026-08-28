<?php

declare(strict_types=1);

namespace PDF;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Response;
use PDF\Exceptions\PdfException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Pdf
{
    private string $contentHtml = '';
    private ?string $headerHtml = null;
    private ?string $footerHtml = null;
    private ?string $watermarkHtml = null;

    private ?string $paper = null;
    private ?string $orientation = null;
    private ?string $margin = null;
    private ?string $marginTop = null;
    private ?string $marginBottom = null;
    private ?string $marginLeft = null;
    private ?string $marginRight = null;

    private ?string $headerHeight = null;
    private ?string $footerHeight = null;
    private ?string $headerSpacing = null;
    private ?string $footerSpacing = null;
    private ?string $headerOffset = null;
    private ?string $footerOffset = null;

    private ?float $watermarkOpacity = null;
    private ?bool $watermarkBehind = null;

    private ?float $scale = null;
    private ?int $pageOffset = null;
    private ?int $totalOffset = null;

    private ?string $title = null;
    private ?string $author = null;
    private ?string $subject = null;
    private ?string $keywords = null;
    private ?string $baseUrl = null;

    private bool $quiet = true;
    private ?int $timeout = null;
    private ?string $chromePath = null;
    private ?string $binaryPath = null;
    private ?string $tempDirectory = null;

    public function __construct()
    {
        $this->initializeDefaults();
    }

    public static function make(): self
    {
        return new self();
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

    public function footer(string|Renderable $html): self
    {
        $this->footerHtml = $this->renderHtml($html);

        return $this;
    }

    public function watermark(string|Renderable $html): self
    {
        $this->watermarkHtml = $this->renderHtml($html);

        return $this;
    }

    public function paper(string $paper): self
    {
        $this->paper = $paper;

        return $this;
    }

    public function orientation(string $orientation): self
    {
        $this->orientation = $orientation;

        return $this;
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

    public function watermarkBehind(bool $behind = true): self
    {
        $this->watermarkBehind = $behind;

        return $this;
    }

    public function scale(float $scale): self
    {
        $this->scale = $scale;

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

    public function quiet(bool $quiet = true): self
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

    public function get(): string
    {
        return $this->renderPdfToBuffer();
    }

    public function save(string $path): string
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdfData = $this->get();

        if (file_put_contents($path, $pdfData) === false) {
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
        $formattedFilename = str_ends_with(strtolower($filename), '.pdf') ? $filename : "{$filename}.pdf";

        return new Response($this->get(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$formattedFilename}\"",
        ]);
    }

    public function inline(string $filename = 'document.pdf'): Response
    {
        $formattedFilename = str_ends_with(strtolower($filename), '.pdf') ? $filename : "{$filename}.pdf";

        return new Response($this->get(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$formattedFilename}\"",
        ]);
    }

    public function resolveBinaryPath(): string
    {
        if ($this->binaryPath && file_exists($this->binaryPath)) {
            return $this->binaryPath;
        }

        $candidates = [
            $this->binaryPath,
            function_exists('storage_path') ? storage_path('pdf' . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'pdf.exe' : 'pdf')) : null,
            function_exists('base_path') ? base_path('pdf' . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'pdf.exe' : 'pdf')) : null,
            function_exists('base_path') ? base_path(PHP_OS_FAMILY === 'Windows' ? 'pdf.exe' : 'pdf') : null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                return realpath($candidate) ?: $candidate;
            }
        }

        $executableFinder = new ExecutableFinder();
        $inPath = $executableFinder->find(PHP_OS_FAMILY === 'Windows' ? 'pdf.exe' : 'pdf');

        if ($inPath) {
            return $inPath;
        }

        throw PdfException::binaryNotFound($this->binaryPath ?? 'storage/pdf/pdf or system PATH');
    }

    private function initializeDefaults(): void
    {
        $config = function_exists('config') ? config('pdf', []) : [];

        $this->binaryPath     = $config['binary_path'] ?? null;
        $this->chromePath     = $config['chrome_path'] ?? null;
        $this->timeout        = $config['timeout'] ?? 120;
        $this->tempDirectory  = $config['temp_path'] ?? null;
    }

    private function renderHtml(string|Renderable $html): string
    {
        return match (true) {
            $html instanceof Renderable => $html->render(),
            default                     => (string) $html,
        };
    }

    private function renderPdfToBuffer(): string
    {
        $binary = $this->resolveBinaryPath();
        $tempFiles = [];
        $tempDir = $this->tempDirectory ?: sys_get_temp_dir();

        try {
            $command = [$binary];

            $hasFragments = $this->headerHtml !== null || $this->footerHtml !== null || $this->watermarkHtml !== null;

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

            if ($this->headerHtml !== null) {
                $headerPath = $this->createTempHtmlFile($tempDir, 'pdf_header_', $this->headerHtml);
                $tempFiles[] = $headerPath;
                $command[] = '--header';
                $command[] = $headerPath;
            }

            if ($this->footerHtml !== null) {
                $footerPath = $this->createTempHtmlFile($tempDir, 'pdf_footer_', $this->footerHtml);
                $tempFiles[] = $footerPath;
                $command[] = '--footer';
                $command[] = $footerPath;
            }

            if ($this->watermarkHtml !== null) {
                $watermarkPath = $this->createTempHtmlFile($tempDir, 'pdf_watermark_', $this->watermarkHtml);
                $tempFiles[] = $watermarkPath;
                $command[] = '--watermark';
                $command[] = $watermarkPath;
            }

            if ($this->paper !== null) {
                $command[] = '--paper';
                $command[] = $this->paper;
            }

            if ($this->orientation !== null) {
                $command[] = '--orientation';
                $command[] = $this->orientation;
            }

            if ($this->margin !== null) {
                $command[] = '--margin';
                $command[] = $this->margin;
            }

            if ($this->marginTop !== null && $this->marginTop !== '0') {
                $command[] = '--margin-top';
                $command[] = $this->marginTop;
            }

            if ($this->marginBottom !== null && $this->marginBottom !== '0') {
                $command[] = '--margin-bottom';
                $command[] = $this->marginBottom;
            }

            if ($this->marginLeft !== null && $this->marginLeft !== '0') {
                $command[] = '--margin-left';
                $command[] = $this->marginLeft;
            }

            if ($this->marginRight !== null && $this->marginRight !== '0') {
                $command[] = '--margin-right';
                $command[] = $this->marginRight;
            }

            if ($this->headerHeight !== null && $this->headerHeight !== '0') {
                $command[] = '--header-height';
                $command[] = $this->headerHeight;
            }

            if ($this->footerHeight !== null && $this->footerHeight !== '0') {
                $command[] = '--footer-height';
                $command[] = $this->footerHeight;
            }

            if ($this->headerSpacing !== null && $this->headerSpacing !== '0') {
                $command[] = '--header-spacing';
                $command[] = $this->headerSpacing;
            }

            if ($this->footerSpacing !== null && $this->footerSpacing !== '0') {
                $command[] = '--footer-spacing';
                $command[] = $this->footerSpacing;
            }

            if ($this->headerOffset !== null && $this->headerOffset !== '0') {
                $command[] = '--header-offset';
                $command[] = $this->headerOffset;
            }

            if ($this->footerOffset !== null && $this->footerOffset !== '0') {
                $command[] = '--footer-offset';
                $command[] = $this->footerOffset;
            }

            if ($this->watermarkOpacity !== null) {
                $command[] = '--watermark-opacity';
                $command[] = (string) $this->watermarkOpacity;
            }

            if ($this->watermarkBehind !== null && $this->watermarkBehind) {
                $command[] = '--watermark-behind';
            }

            if ($this->scale !== null) {
                $command[] = '--scale';
                $command[] = (string) $this->scale;
            }

            if ($this->pageOffset !== null && $this->pageOffset !== 0) {
                $command[] = '--page-offset';
                $command[] = (string) $this->pageOffset;
            }

            if ($this->totalOffset !== null && $this->totalOffset !== 0) {
                $command[] = '--total-offset';
                $command[] = (string) $this->totalOffset;
            }

            if ($this->title !== null && $this->title !== '') {
                $command[] = '--title';
                $command[] = $this->title;
            }

            if ($this->author !== null && $this->author !== '') {
                $command[] = '--author';
                $command[] = $this->author;
            }

            if ($this->subject !== null && $this->subject !== '') {
                $command[] = '--subject';
                $command[] = $this->subject;
            }

            if ($this->keywords !== null && $this->keywords !== '') {
                $command[] = '--keywords';
                $command[] = $this->keywords;
            }

            if ($this->baseUrl !== null && $this->baseUrl !== '') {
                $command[] = '--base-url';
                $command[] = $this->baseUrl;
            }

            if ($this->chromePath !== null && $this->chromePath !== '') {
                $command[] = '--chrome';
                $command[] = $this->chromePath;
            }

            if ($this->timeout !== null) {
                $command[] = '--timeout';
                $command[] = (string) $this->timeout;
            }

            if ($this->quiet) {
                $command[] = '--quiet';
            }

            $process = new Process($command);
            $process->setTimeout($this->timeout ?? 120);

            if (!$hasFragments) {
                $process->setInput($this->contentHtml);
            }

            $process->run();

            if (!$process->isSuccessful()) {
                throw PdfException::executionFailed(
                    $process->getErrorOutput(),
                    (int) $process->getExitCode()
                );
            }

            return $process->getOutput();
        } finally {
            foreach ($tempFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
        }
    }

    private function createTempHtmlFile(string $dir, string $prefix, string $content): string
    {
        $filePath = tempnam($dir, $prefix);

        if ($filePath === false) {
            $filePath = $dir . DIRECTORY_SEPARATOR . uniqid($prefix, true) . '.html';
        }

        file_put_contents($filePath, $content);

        return $filePath;
    }
}
