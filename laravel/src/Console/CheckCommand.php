<?php

declare(strict_types=1);

namespace PDF\Console;

use Illuminate\Console\Command;
use PDF\Pdf;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\table;

class CheckCommand extends Command
{
    protected $signature = 'snpdf:check';

    protected $description = 'Verify that the snpdf binary and headless Chrome/Chromium are installed and working';

    public function handle(): int
    {
        intro('Checking snpdf Requirements & Environment');

        $binaryResult = $this->checkPdfBinary();
        $chromeResult = $this->checkChromeInstallation();

        table(
            headers: ['Component', 'Status', 'Details'],
            rows: [
                ['snpdf Binary', $binaryResult['ok'] ? '✓ OK' : '✗ Failed', $binaryResult['details']],
                ['Chrome / Chromium', $chromeResult['ok'] ? '✓ OK' : '✗ Failed', $chromeResult['details']],
            ]
        );

        if ($binaryResult['ok'] && $chromeResult['ok']) {
            outro('✓ All systems operational! snpdf is ready to generate PDFs.');

            return self::SUCCESS;
        }

        error('✗ One or more checks failed. Please review the table above.');
        if (! $binaryResult['ok']) {
            note('Run php artisan snpdf:install to install the binary automatically.');
        }

        return self::FAILURE;
    }

    /**
     * @return array{ok: bool, details: string}
     */
    private function checkPdfBinary(): array
    {
        try {
            /** @var Pdf $builder */
            $builder = resolve('pdf');
            $binaryPath = $builder->resolveBinaryPath();

            if (! is_executable($binaryPath) && PHP_OS_FAMILY !== 'Windows') {
                return [
                    'ok' => FALSE,
                    'details' => "Found at {$binaryPath} but not executable (run chmod +x).",
                ];
            }

            $process = new Process([$binaryPath, '--version']);
            $process->run();

            if ($process->isSuccessful()) {
                $version = trim($process->getOutput());

                return [
                    'ok' => TRUE,
                    'details' => "{$binaryPath} ({$version})",
                ];
            }

            return [
                'ok' => FALSE,
                'details' => 'Execution failed: ' . trim($process->getErrorOutput()),
            ];
        } catch (Throwable $throwable) {
            return [
                'ok' => FALSE,
                'details' => $throwable->getMessage(),
            ];
        }
    }

    /**
     * @return array{ok: bool, details: string}
     */
    private function checkChromeInstallation(): array
    {
        $chromePath = config('pdf.chrome_path');

        if ($chromePath && file_exists($chromePath)) {
            $version = $this->getBrowserVersion($chromePath);

            return [
                'ok' => TRUE,
                'details' => "Configured: {$chromePath}" . ($version ? " ({$version})" : ''),
            ];
        }

        $discoveredPath = $this->detectChromeExecutable();

        if ($discoveredPath) {
            $version = $this->getBrowserVersion($discoveredPath);

            return [
                'ok' => TRUE,
                'details' => "Detected: {$discoveredPath}" . ($version ? " ({$version})" : ''),
            ];
        }

        return [
            'ok' => FALSE,
            'details' => 'No Chrome, Chromium, Brave, or Edge executable found.',
        ];
    }

    private function getBrowserVersion(string $path): ?string
    {
        $process = new Process([$path, '--version']);
        $process->run();

        return $process->isSuccessful() ? trim($process->getOutput()) : NULL;
    }

    private function detectChromeExecutable(): ?string
    {
        $candidates = match (PHP_OS_FAMILY) {
            'Windows' => [
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
                (getenv('LOCALAPPDATA') ?: '') . '\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
                'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
                'C:\\Program Files\\BraveSoftware\\Brave-Browser\\Application\\brave.exe',
            ],
            'Darwin' => [
                '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
                '/Applications/Chromium.app/Contents/MacOS/Chromium',
                '/Applications/Brave Browser.app/Contents/MacOS/Brave Browser',
                '/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge',
            ],
            default => [
                '/usr/bin/google-chrome',
                '/usr/bin/google-chrome-stable',
                '/usr/bin/chromium',
                '/usr/bin/chromium-browser',
                '/snap/bin/chromium',
                '/usr/bin/brave-browser',
                '/usr/bin/microsoft-edge',
            ],
        };

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                return $candidate;
            }
        }

        $executableFinder = new ExecutableFinder;

        return $executableFinder->find('google-chrome')
            ?? $executableFinder->find('google-chrome-stable')
            ?? $executableFinder->find('chromium')
            ?? $executableFinder->find('chromium-browser')
            ?? $executableFinder->find('chrome')
            ?? $executableFinder->find('msedge');
    }
}
