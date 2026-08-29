<?php

declare(strict_types=1);

namespace PDF\Services;

use Illuminate\Support\Facades\View;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Throwable;

class ViewFinderService
{
    /**
     * Get a sorted list of all available view names in the application and vendor namespaces.
     *
     * @return list<string>
     */
    public static function getAvailableViews(): array
    {
        $views = [];

        try {
            $finder = View::getFinder();

            // 1. Scan primary view paths (e.g. resources/views)
            if (method_exists($finder, 'getPaths')) {
                foreach ($finder->getPaths() as $path) {
                    if (is_dir($path)) {
                        self::scanDirectoryForViews($path, '', $views);
                    }
                }
            }

            // 2. Scan namespaced view hints (e.g. 'pdf::...', vendor views)
            if (method_exists($finder, 'getHints')) {
                foreach ($finder->getHints() as $namespace => $hints) {
                    foreach ((array) $hints as $hintPath) {
                        if (is_dir($hintPath)) {
                            self::scanDirectoryForViews($hintPath, "{$namespace}::", $views);
                        }
                    }
                }
            }
        } catch (Throwable) {
            // Fallback if View finder reflection or resolution fails in testing / non-booted environments
            $defaultResourceViews = function_exists('resource_path') ? resource_path('views') : NULL;
            if ($defaultResourceViews && is_dir($defaultResourceViews)) {
                self::scanDirectoryForViews($defaultResourceViews, '', $views);
            }
        }

        $views = array_values(array_unique(array_filter($views)));
        sort($views);

        return $views;
    }

    /**
     * Scan a directory recursively for blade/php view templates.
     *
     * @param  list<string>  $views
     */
    private static function scanDirectoryForViews(string $dir, string $prefix, array &$views): void
    {
        if (! is_dir($dir)) {
            return;
        }

        try {
            $finder = new Finder;
            $finder->files()->in($dir)->name(['*.blade.php', '*.php']);

            foreach ($finder as $file) {
                /** @var SplFileInfo $file */
                $relativePath = str_replace('\\', '/', $file->getRelativePathname());

                // Remove .blade.php or .php extension
                $viewName = preg_replace('/\.(blade\.php|php)$/i', '', $relativePath);

                if ($viewName === NULL || $viewName === '') {
                    continue;
                }

                // Convert directory separators to dot notation
                $dottedName = str_replace('/', '.', $viewName);

                $views[] = $prefix . $dottedName;
            }
        } catch (Throwable) {
            // Fallback using glob if Symfony Finder encounters permission issues
            $files = glob($dir . '/*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file) && (str_ends_with($file, '.blade.php') || str_ends_with($file, '.php'))) {
                        $basename = basename($file);
                        $viewName = preg_replace('/\.(blade\.php|php)$/i', '', $basename);
                        if ($viewName) {
                            $views[] = $prefix . $viewName;
                        }
                    }
                }
            }
        }
    }
}
