<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Updates directory
    |--------------------------------------------------------------------------
    |
    | Directory served by nginx at /updates/ (manifest + release binaries).
    | In Docker this is /var/www/updates (mounted from ./updates).
    | For a bare-metal dev run we fall back to <project-root>/updates.
    |
    */
    'path' => env('UPDATES_PATH', '/var/www/updates'),

    'platforms' => ['android', 'windows'],

    // Allowed release file extensions per platform (defence against uploading
    // servable content such as .php/.html/.svg into the web-served directory).
    'allowed_extensions' => [
        'android' => ['apk', 'aab'],
        'windows' => ['msi', 'exe', 'zip'],
    ],

    'max_size_kb' => (int) env('UPDATES_MAX_SIZE_KB', 512000), // 500 MB

    // Optional server-side signature verification of uploaded binaries.
    // When enabled, a verifier command must be configured per platform or
    // uploads are rejected (fail closed). %s is replaced with the file path.
    // Examples:
    //   android: apksigner verify --print-certs %s
    //   windows: osslsigncode verify -in %s
    'verify_signature' => (bool) env('UPDATES_VERIFY_SIGNATURE', false),

    'signature_commands' => [
        'android' => (string) env('UPDATES_VERIFY_ANDROID', ''),
        'windows' => (string) env('UPDATES_VERIFY_WINDOWS', ''),
    ],
];
