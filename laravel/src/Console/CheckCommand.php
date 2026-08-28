<?php

declare(strict_types=1);

namespace PDF\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class CheckCommand extends Command
{
    protected $signature = 'pdf:check';

    protected $description = 'Verify that the pdf binary and headless Chrome/Chromium are installed and working';

    public function handle(): int
    {
        $this->info('Checking pdf requirements and environment...');
        $this->newLine();

        $binaryOk = $this->checkPdfBinary();
        $this->newLine();

        $chromeOk = $this->checkChromeInstallation();
        $this->newLine();

        if ($binaryOk && $chromeOk) {
            $this->info('✓ All systems operational! pdf is ready to generate PDFs.');

            return self::SUCCESS;
        }

        $this->error('✗ One or more checks failed. Please review the errors above.');

        return self::FAILURE;
    }

    private function checkPdfBinary(): bool
    {
        $this->line('<comment>1. Checking pdf Binary:</comment>');

        try {
            /** @var \PDF\Pdf $builder */
            $builder = app('pdf');
            $binaryPath = $builder->resolveBinaryPath();

            $this->line("   - Binary located at: <info>{$binaryPath}</info>");

            if (!is_executable($binaryPath) && PHP_OS_FAMILY !== 'Windows') {
                $this->error("   - File exists but is not executable. Run 'chmod +x {$binaryPath}'.");

                return false;
            }

            $process = new Process([$binaryPath, '--version']);
            $process->run();

            if ($process->isSuccessful()) {
                $version = trim($process->getOutput());
                $this->line("   - Binary version:    <info>{$version}</info>");
                $this->info('   ✓ pdf binary is working properly.');

                return true;
            }

            $this->error('   ✗ Failed executing pdf binary: ' . trim($process->getErrorOutput()));

            return false;
        } catch (\Throwable $throwable) {
            $this->error("   ✗ {$throwable->getMessage()}");
            $this->line("     Run <comment>php artisan pdf:install</comment> to install it automatically.");

            return false;
        }
    }

    private function checkChromeInstallation(): bool
    {
        $this->line('<comment>2. Checking Chrome / Chromium Engine:</comment>');

        $chromePath = config('pdf.chrome_path');

        if ($chromePath && file_exists($chromePath)) {
            $this->line("   - Using configured Chrome path: <info>{$chromePath}</info>");

            return $this->testBrowserExecutable($chromePath);
        }

        $discoveredPath = $this->detectChromeExecutable();

        if ($discoveredPath) {
            $this->line("   - Auto-detected browser executable: <info>{$discoveredPath}</info>");

            return $this->testBrowserExecutable($discoveredPath);
        }

        $this->error('   ✗ No Chrome, Chromium, Brave, or Edge executable found in standard locations.');
        $this->line('     Please install Google Chrome / Chromium or configure PDF_CHROME_PATH in your .env file.');

        return false;
    }

    private function testBrowserExecutable(string $path): bool
    {
        $process = new Process([$path, '--version']);
        $process->run();

        if ($process->isSuccessful()) {
            $version = trim($process->getOutput());
            $this->line("   - Browser version:   <info>{$version}</info>");
            $this->info('   ✓ Chrome/Chromium is installed and accessible.');

            return true;
        }

        $this->warn("   - Found executable at {$path} but could not get version: " . trim($process->getErrorOutput()));

        return true;
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

        $finder = new ExecutableFinder();

        return $finder->find('google-chrome')
            ?? $finder->find('google-chrome-stable')
            ?? $finder->find('chromium')
            ?? $finder->find('chromium-browser')
            ?? $finder->find('chrome')
            ?? $finder->find('msedge');
    }
}
