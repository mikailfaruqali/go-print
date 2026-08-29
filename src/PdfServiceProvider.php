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
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'pdf');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->registerRoutes();
        $this->registerPublishing();
        $this->registerCommands();
    }

    private function registerRoutes(): void
    {
        if (config('pdf.routes_enabled', TRUE)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }

    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/pdf.php' => config_path('pdf.php'),
            ], 'pdf-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/pdf'),
            ], 'pdf-views');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'pdf-migrations');
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
