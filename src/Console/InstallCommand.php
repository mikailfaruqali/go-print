<?php

declare(strict_types=1);

namespace PDF\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

use Throwable;
use ZipArchive;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    protected $signature = 'pdf:install
                            {--force : Force download and overwrite existing binary}
                            {--tag=latest : GitHub release tag to download}';

    protected $description = 'Download and install the pdf binary for your operating system and architecture';

    public function handle(): int
    {
        intro('Installing pdf Binary');

        $os = $this->detectOs();
        $arch = $this->detectArch();

        note("Environment detected: {$os} ({$arch})");

        $isWindows = $os === 'windows';
        $binaryFileName = $isWindows ? 'pdf.exe' : 'pdf';
        $archiveExtension = $isWindows ? '.zip' : '.tar.gz';

        $destinationDirectory = storage_path('pdf');
        $targetBinaryPath = $destinationDirectory . DIRECTORY_SEPARATOR . $binaryFileName;

        if (File::exists($targetBinaryPath) && ! $this->option('force')) {
            warning("Binary already exists at: {$targetBinaryPath}");

            $overwrite = confirm(
                label: 'Do you want to re-download and overwrite it?',
                default: FALSE,
            );

            if (! $overwrite) {
                $this->verifyBinary($targetBinaryPath);
                outro('pdf installation skipped.');

                return self::SUCCESS;
            }
        }

        if (! File::isDirectory($destinationDirectory)) {
            File::makeDirectory($destinationDirectory, 0755, TRUE, TRUE);
        }

        $tag = (string) $this->option('tag');

        $downloadUrl = spin(
            fn (): ?string => $this->resolveDownloadUrl($os, $arch, $archiveExtension, $tag),
            "Fetching release information ({$tag})..."
        );

        if (! $downloadUrl) {
            error("Unable to resolve release asset download URL for {$os}-{$arch}.");
            note("You can manually place the binary in: {$targetBinaryPath}");

            return self::FAILURE;
        }

        $tempArchive = tempnam(sys_get_temp_dir(), 'pdf_download_') . $archiveExtension;

        try {
            $downloaded = spin(
                function () use ($downloadUrl, $tempArchive) {
                    $response = Http::timeout(60)
                        ->withHeaders(['User-Agent' => 'MikailFaruqAli-PDF-Laravel-Installer'])
                        ->sink($tempArchive)
                        ->get($downloadUrl);

                    return $response->successful();
                },
                'Downloading pdf binary...'
            );

            if (! $downloaded) {
                error('Failed to download binary asset from GitHub release.');

                return self::FAILURE;
            }

            spin(
                function () use ($tempArchive, $destinationDirectory, $isWindows, $binaryFileName, $targetBinaryPath): void {
                    $this->extractBinary($tempArchive, $destinationDirectory, $isWindows, $binaryFileName);

                    if (! $isWindows && File::exists($targetBinaryPath)) {
                        chmod($targetBinaryPath, 0755);
                    }
                },
                'Extracting binary archive...'
            );

            info("Binary successfully placed at: {$targetBinaryPath}");

            $this->verifyBinary($targetBinaryPath);

            outro('pdf installation completed successfully!');

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            error("Installation error: {$throwable->getMessage()}");

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
            ? 'https://api.github.com/repos/mikailfaruqali/go-print/releases/latest'
            : "https://api.github.com/repos/mikailfaruqali/go-print/releases/tags/{$tag}";

        $response = Http::withHeaders([
            'User-Agent' => 'MikailFaruqAli-PDF-Laravel-Installer',
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

        return "https://github.com/mikailfaruqali/go-print/releases/download/{$releaseTag}/pdf_{$os}_{$arch}{$extension}";
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
        $process = new Process([$binaryPath, '--version']);
        $process->run();

        if ($process->isSuccessful()) {
            $versionOutput = trim($process->getOutput());
            note("pdf version: {$versionOutput}");
        } else {
            warning("Could not execute '{$binaryPath} --version': " . trim($process->getErrorOutput()));
        }
    }
}
