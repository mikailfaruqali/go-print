<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | PDF Binary Path
    |--------------------------------------------------------------------------
    |
    | Absolute path to the pdf executable binary. If set to null, the package
    | will search in storage_path('pdf/pdf'), base_path('pdf'), or PATH.
    | You can download the binary automatically using 'php artisan pdf:install'.
    |
    */
    'binary_path' => env('PDF_BINARY_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Headless Chrome Path Override
    |--------------------------------------------------------------------------
    |
    | By default, pdf automatically detects Google Chrome, Chromium, Brave,
    | or Microsoft Edge installed on your system. Set an explicit path if you
    | want to override the detected executable.
    |
    */
    'chrome_path' => env('PDF_CHROME_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Execution Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum execution timeout in seconds for the PDF rendering process.
    |
    */
    'timeout' => (int) env('PDF_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Temp Directory
    |--------------------------------------------------------------------------
    |
    | Storage path for temporary HTML snippets (content, header, footer).
    | If null, sys_get_temp_dir() is used.
    |
    */
    'temp_path' => env('PDF_TEMP_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Template UI Routes Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable the template configuration routes at /pdf-templates.
    |
    */
    'routes_enabled' => env('PDF_ROUTES_ENABLED', TRUE),

    /*
    |--------------------------------------------------------------------------
    | Template UI Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URI prefix for template studio management routes (default: pdf-templates).
    |
    */
    'route_prefix' => env('PDF_ROUTE_PREFIX', 'pdf-templates'),

    /*
    |--------------------------------------------------------------------------
    | Template UI Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware stack to apply to the template management interface routes.
    | You can pass strings or array of middleware (e.g. ['web', 'auth', 'can:manage-pdf-templates']).
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Supported Template Locales
    |--------------------------------------------------------------------------
    |
    | Locales available in the template management dropdown selector.
    |
    */
    'locales' => ['*', 'en', 'ar', 'ckb', 'ku', 'fr', 'de', 'es', 'tr', 'fa'],
];
