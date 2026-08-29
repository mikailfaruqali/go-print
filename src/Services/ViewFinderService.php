<?php

declare(strict_types=1);

namespace PDF\Services;

use Illuminate\Support\Facades\View;
use Symfony\Component\Finder\Finder;
use Throwable;

class ViewFinderService
{
    public static function getAvailableViews(): array
    {
        $views = [];

        try {
            $finder = View::getFinder();

            if (method_exists($finder, 'getPaths')) {
                foreach ($finder->getPaths() as $path) {
                    if (is_dir($path)) {
                        self::scanDirectoryForViews($path, '', $views);
                    }
                }
            }

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
            $defaultResourceViews = function_exists('resource_path') ? resource_path('views') : NULL;
            if ($defaultResourceViews && is_dir($defaultResourceViews)) {
                self::scanDirectoryForViews($defaultResourceViews, '', $views);
            }
        }

        $views = array_values(array_unique(array_filter($views)));
        sort($views);

        if (! in_array('*', $views, TRUE)) {
            array_unshift($views, '*');
        }

        return $views;
    }

    private static function scanDirectoryForViews(string $dir, string $prefix, array &$views): void
    {
        if (! is_dir($dir)) {
            return;
        }

        try {
            $finder = new Finder;
            $finder->files()->in($dir)->name(['*.blade.php', '*.php']);

            foreach ($finder as $file) {
                $relativePath = str_replace('\\', '/', $file->getRelativePathname());
                $viewName = preg_replace('/\.(blade\.php|php)$/i', '', $relativePath);

                if ($viewName === NULL || $viewName === '') {
                    continue;
                }

                $dottedName = str_replace('/', '.', $viewName);

                $views[] = $prefix . $dottedName;
            }
        } catch (Throwable) {
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
