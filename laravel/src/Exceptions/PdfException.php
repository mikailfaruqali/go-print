<?php

declare(strict_types=1);

namespace PDF\Exceptions;

use RuntimeException;

class PdfException extends RuntimeException
{
    public static function executionFailed(string $errorOutput, int $exitCode): self
    {
        $message = trim($errorOutput) ?: 'Unknown error occurred while generating PDF.';

        return new self("pdf process failed with exit code {$exitCode}: {$message}");
    }

    public static function binaryNotFound(string $binaryPath): self
    {
        return new self("pdf binary not found or not executable at: {$binaryPath}. Run 'php artisan pdf:install' to install it.");
    }

    public static function chromeNotFound(?string $chromePath = null): self
    {
        $location = $chromePath ? "at specified path: {$chromePath}" : 'in standard system paths';

        return new self("Chrome or Chromium executable not found {$location}. Please install Chrome or configure 'chrome_path' in config/pdf.php.");
    }

    public static function saveFailed(string $path): self
    {
        return new self("Failed to save PDF to destination path: {$path}");
    }
}
