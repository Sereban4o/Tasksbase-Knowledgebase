<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 1С integration
    |--------------------------------------------------------------------------
    |
    | Inbound requests from 1С are authenticated with a bearer token and an
    | HMAC signature (hex sha256 of "{timestamp}.{rawBody}") to prevent
    | tampering and replay.
    |
    */
    'enabled' => env('ONE_C_ENABLED', false),

    // Base URL of the 1С HTTP service used for outgoing calls (Laravel -> 1С).
    'base_url' => env('ONE_C_BASE_URL'),

    // Token for inbound requests (1С -> Laravel).
    'token' => env('ONE_C_TOKEN'),

    // Shared secret for HMAC signatures.
    'hmac_secret' => env('ONE_C_HMAC_SECRET'),

    // Optional IP allowlist for inbound 1С requests (comma separated in env).
    // Empty = no IP restriction (rely on token/HMAC).
    'allowed_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ONE_C_ALLOWED_IPS', '')),
    ))),

    // Allowed clock skew for the X-Timestamp header, seconds.
    'tolerance' => (int) env('ONE_C_TIMESTAMP_TOLERANCE', 300),

    'timeout' => (int) env('ONE_C_TIMEOUT', 30),

    'verify' => (bool) env('ONE_C_VERIFY', true),
];
