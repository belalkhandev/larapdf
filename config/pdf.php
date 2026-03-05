<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Node and NPM Binary Paths
    |--------------------------------------------------------------------------
    |
    | Browsershot requires Node.js and NPM/Puppeteer. Specify the paths if
    | they are not in your system's PATH.
    |
    */
    'node_binary'     => env('PDF_NODE_BINARY', 'node'),
    'npm_binary'      => env('PDF_NPM_BINARY', 'npm'),

    /*
    |--------------------------------------------------------------------------
    | Chrome Path
    |--------------------------------------------------------------------------
    |
    | If you have a specific version of Chrome/Chromium installed, specify the 
    | absolute path to the executable here.
    |
    */
    'chrome_path'     => env('PDF_CHROME_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | Puppeteer Cache Path
    |--------------------------------------------------------------------------
    |
    | High-level configuration for where Puppeteer should look for/install
    | browsers. This sets the PUPPETEER_CACHE_DIR environment variable.
    | Useful for CI/CD, shared hosting, or when using pnpm.
    |
    */
    'puppeteer_cache_path' => env('PDF_PUPPETEER_CACHE_PATH', storage_path('app/larapdf/browser')),

    'disable_sandbox' => env('PDF_DISABLE_SANDBOX', false),
    'font_render_hinting' => env('PDF_FONT_RENDER_HINTING', 'none'),
    'chrome_args' => [],


    /*
    |--------------------------------------------------------------------------
    | Default PDF Settings
    |--------------------------------------------------------------------------
    |
    | These values will be used when they are not explicitly set via the API.
    |
    */
    'default_paper'   => 'A4',
    'default_orientation' => 'portrait',
    'default_margins' => [10, 10, 10, 10], // top, right, bottom, left in mm

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds to wait for PDF generation.
    |
    */
    'timeout'         => 60,

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | The default disk to use when using the `saveToDisk` method.
    |
    */
    'disk'            => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Tailwind Support
    |--------------------------------------------------------------------------
    |
    | The CDN URL to used when `withTailwind()` is called without arguments.
    |
    */
    'tailwind_cdn'    => 'https://cdn.tailwindcss.com',
];
