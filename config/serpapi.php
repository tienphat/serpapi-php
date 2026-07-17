<?php

// Usage: $client = new SerpApiClient(SerpApiConfig::fromArray(config('serpapi')));
// Publish: php artisan vendor:publish --tag=serpapi-config

return [

    // API token — https://serpapi.org/api-key
    'token'    => env('SERPAPI_TOKEN', ''),

    // Default country code (gl). See config/serpapi-countries.php for all values.
    'country'  => env('SERPAPI_COUNTRY', 'US'),

    // Default language code (hl). See config/serpapi-languages.php for all values.
    'language' => env('SERPAPI_LANGUAGE', 'en'),

    // cURL timeout in seconds.
    'timeout'  => (int) env('SERPAPI_TIMEOUT', 30),

    // Override only for self-hosted / testing.
    'base_url' => env('SERPAPI_BASE_URL', 'https://serpapi.org/api/v1'),

];
