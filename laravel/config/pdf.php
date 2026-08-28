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
];
