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

    private bool $quiet = TRUE;

    private ?int $timeout;

    private ?string $chromePath;

    private ?string $binaryPath;

    private ?string $tempDirectory;

    public function __construct()
    {
        $this->initializeDefaults();
    }

    public static function make(): self
    {
        return new self;
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

    public function get(): string
    {
        return $this->renderPdfToBuffer();
    }

    public function save(string $path): string
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, TRUE);
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
        $formattedFilename = str_ends_with(strtolower($filename), '.pdf') ? $filename : "{$filename}.pdf";

        return new Response($this->get(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$formattedFilename}\"",
        ]);
    }

    public function inline(string $filename = 'document.pdf'): Response
    {
        $formattedFilename = str_ends_with(strtolower($filename), '.pdf') ? $filename : "{$filename}.pdf";

        return new Response($this->get(), 200, [
            'Content-Type' => 'application/pdf',
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
            function_exists('storage_path') ? storage_path('pdf' . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'pdf.exe' : 'pdf')) : NULL,
            function_exists('base_path') ? base_path('pdf' . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'pdf.exe' : 'pdf')) : NULL,
            function_exists('base_path') ? base_path(PHP_OS_FAMILY === 'Windows' ? 'pdf.exe' : 'pdf') : NULL,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                return realpath($candidate) ?: $candidate;
            }
        }

        $executableFinder = new ExecutableFinder;
        $inPath = $executableFinder->find(PHP_OS_FAMILY === 'Windows' ? 'pdf.exe' : 'pdf');

        if ($inPath) {
            return $inPath;
        }

        throw PdfException::binaryNotFound($this->binaryPath ?? 'storage/pdf/pdf or system PATH');
    }

    private function initializeDefaults(): void
    {
        $config = function_exists('config') ? config('pdf', []) : [];

        $this->binaryPath = $config['binary_path'] ?? NULL;
        $this->chromePath = $config['chrome_path'] ?? NULL;
        $this->timeout = $config['timeout'] ?? 120;
        $this->tempDirectory = $config['temp_path'] ?? NULL;
    }

    private function renderHtml(string|Renderable $html): string
    {
        return match (TRUE) {
            $html instanceof Renderable => $html->render(),
            default => $html,
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
            $process->setTimeout($this->timeout ?? 120);

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

            return $process->getOutput();
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

        file_put_contents($filePath, $content);

        return $filePath;
    }
}
