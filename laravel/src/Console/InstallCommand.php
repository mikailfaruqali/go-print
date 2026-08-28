<?php

declare(strict_types=1);

namespace PDF\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class InstallCommand extends Command
{
    protected $signature = 'snpdf:install
                            {--force : Force download and overwrite existing binary}
                            {--tag=latest : GitHub release tag to download}';

    protected $description = 'Download and install the snpdf binary for your operating system and architecture';

    public function handle(): int
    {
        $this->info('Detecting system environment...');

        $os = $this->detectOs();
        $arch = $this->detectArch();

        $this->line(" - Operating System: <comment>{$os}</comment>");
        $this->line(" - Architecture:     <comment>{$arch}</comment>");

        $isWindows = $os === 'windows';
        $binaryFileName = $isWindows ? 'snpdf.exe' : 'snpdf';
        $archiveExtension = $isWindows ? '.zip' : '.tar.gz';

        $destinationDirectory = storage_path('snpdf');
        $targetBinaryPath = $destinationDirectory . DIRECTORY_SEPARATOR . $binaryFileName;

        if (File::exists($targetBinaryPath) && ! $this->option('force')) {
            $this->warn("Binary already exists at: {$targetBinaryPath}");

            if (! $this->confirm('Do you want to re-download and overwrite it?', FALSE)) {
                $this->verifyBinary($targetBinaryPath);

                return self::SUCCESS;
            }
        }

        if (! File::isDirectory($destinationDirectory)) {
            File::makeDirectory($destinationDirectory, 0755, TRUE, TRUE);
        }

        $tag = (string) $this->option('tag');
        $this->info("Fetching release information ({$tag})...");

        $downloadUrl = $this->resolveDownloadUrl($os, $arch, $archiveExtension, $tag);

        if (! $downloadUrl) {
            $this->error("Unable to resolve release asset download URL for {$os}-{$arch}.");
            $this->line("You can manually place the binary in: <comment>{$targetBinaryPath}</comment>");

            return self::FAILURE;
        }

        $this->info("Downloading from: {$downloadUrl}");
        $tempArchive = tempnam(sys_get_temp_dir(), 'snpdf_download_') . $archiveExtension;

        try {
            $response = Http::timeout(60)
                ->withHeaders(['User-Agent' => 'Snawbar-SNPDF-Laravel-Installer'])
                ->sink($tempArchive)
                ->get($downloadUrl);

            if (! $response->successful()) {
                $this->error("Failed to download binary asset (HTTP {$response->status()}).");

                return self::FAILURE;
            }

            $this->info('Extracting binary archive...');
            $this->extractBinary($tempArchive, $destinationDirectory, $isWindows, $binaryFileName);

            if (! $isWindows && File::exists($targetBinaryPath)) {
                chmod($targetBinaryPath, 0755);
            }

            $this->info("Binary successfully placed at: <comment>{$targetBinaryPath}</comment>");

            $this->verifyBinary($targetBinaryPath);

            $this->newLine();
            $this->info('snpdf installation completed successfully!');

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $this->error("Installation error: {$throwable->getMessage()}");

            return self::FAILURE;
        } finally {
            if (File::exists($tempArchive)) {
                @unlink($tempArchive);
            }
        }
    }

    private function detectOs(): string
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => 'windows',
            'Darwin' => 'darwin',
            'Linux' => 'linux',
            default => strtolower(PHP_OS_FAMILY),
        };
    }

    private function detectArch(): string
    {
        $uname = php_uname('m');

        return match (TRUE) {
            str_contains($uname, 'x86_64'), str_contains($uname, 'amd64') => 'amd64',
            str_contains($uname, 'arm64'), str_contains($uname, 'aarch64') => 'arm64',
            str_contains($uname, '386'), str_contains($uname, 'i686') => '386',
            str_contains($uname, 'arm') => 'arm',
            default => 'amd64',
        };
    }

    private function resolveDownloadUrl(string $os, string $arch, string $extension, string $tag): ?string
    {
        $apiUrl = $tag === 'latest'
            ? 'https://api.github.com/repos/snawbar/snpdf/releases/latest'
            : "https://api.github.com/repos/snawbar/snpdf/releases/tags/{$tag}";

        $response = Http::withHeaders([
            'User-Agent' => 'Snawbar-SNPDF-Laravel-Installer',
            'Accept' => 'application/vnd.github.v3+json',
        ])->get($apiUrl);

        if ($response->successful()) {
            $assets = $response->json('assets', []);

            foreach ($assets as $asset) {
                $name = strtolower((string) ($asset['name'] ?? ''));

                if (str_contains($name, $os) && str_contains($name, $arch)) {
                    return $asset['browser_download_url'] ?? NULL;
                }
            }
        }

        $releaseTag = $tag === 'latest' ? 'v1.0.0' : $tag;

        return "https://github.com/snawbar/snpdf/releases/download/{$releaseTag}/snpdf_{$os}_{$arch}{$extension}";
    }

    private function extractBinary(string $archivePath, string $destinationDir, bool $isWindows, string $binaryName): void
    {
        if ($isWindows) {
            $zipArchive = new ZipArchive;

            if ($zipArchive->open($archivePath) === TRUE) {
                $zipArchive->extractTo($destinationDir);
                $zipArchive->close();
            } else {
                copy($archivePath, $destinationDir . DIRECTORY_SEPARATOR . $binaryName);
            }
        } else {
            $process = new Process(['tar', '-xzf', $archivePath, '-C', $destinationDir]);
            $process->run();

            if (! $process->isSuccessful()) {
                copy($archivePath, $destinationDir . DIRECTORY_SEPARATOR . $binaryName);
            }
        }
    }

    private function verifyBinary(string $binaryPath): void
    {
        $this->info('Verifying snpdf executable...');

        $process = new Process([$binaryPath, '--version']);
        $process->run();

        if ($process->isSuccessful()) {
            $versionOutput = trim($process->getOutput());
            $this->line(" - snpdf version: <info>{$versionOutput}</info>");
        } else {
            $this->warn("Could not execute '{$binaryPath} --version': " . trim($process->getErrorOutput()));
        }
    }
}
