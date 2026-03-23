<?php

return [

    /*
    |──────────────────────────────────────────────────────
    | Azure Document Intelligence
    |──────────────────────────────────────────────────────
    */
    'azure' => [
        'key'      => env('AZURE_DI_KEY'),
        'endpoint' => env('AZURE_DI_ENDPOINT'),
        'version'  => '2024-11-30',          // latest stable API version

        // Polling settings for async analysis
        'poll_max_attempts'  => 30,
        'poll_sleep_seconds' => 2,
    ],

    /*
    |──────────────────────────────────────────────────────
    | File Scanner
    |──────────────────────────────────────────────────────
    */
    'base_path' => env('INVOICE_BASE_PATH', 'D:/shared'),

    // Supported invoice file types
    'extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'tiff', 'tif'],

    // Minimum Azure confidence to accept a field value
    'min_confidence' => (float) env('INVOICE_MIN_CONFIDENCE', 0.60),

    // Save full raw Azure JSON in DB (big storage — dev only)
    'store_raw' => env('INVOICE_STORE_RAW', false),
];
