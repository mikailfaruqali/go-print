<?php

declare(strict_types=1);

namespace PDF;

use Illuminate\Support\ServiceProvider;
use PDF\Console\CheckCommand;
use PDF\Console\InstallCommand;

class PdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pdf.php',
            'pdf'
        );

        $this->app->bind('pdf', function (): Pdf {
            return new Pdf;
        });
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerCommands();
    }

    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/pdf.php' => config_path('pdf.php'),
            ], 'pdf-config');
        }
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                CheckCommand::class,
            ]);
        }
    }
}
