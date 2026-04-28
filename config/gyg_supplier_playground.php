<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GetYourGuide supplier API playground (UI tester)
    |--------------------------------------------------------------------------
    |
    | Sends server-side HTTP requests to a supplier base URL using Basic Auth,
    | matching the official Supplier-side OpenAPI paths. Enable explicitly in
    | non-local environments to avoid accidental exposure.
    |
    */

    'enabled' => (bool) env('GYG_SUPPLIER_PLAYGROUND_ENABLED', false),

    'http_timeout_seconds' => (int) env('GYG_SUPPLIER_PLAYGROUND_TIMEOUT', 45),

    'default_gyg_supplier_api_base_url' => (string) env(
        'GYG_SUPPLIER_PLAYGROUND_DEFAULT_GYG_HOST_BASE_URL',
        'https://supplier-api.getyourguide.com/sandbox'
    ),

    /*
    |--------------------------------------------------------------------------
    | Outbound operations (supplier-api-gyg-endpoints.yaml)
    |--------------------------------------------------------------------------
    |
    | Host-side playground operations may only target these hostnames (no
    | open proxy). Comma-separated; default is GetYourGuide production API host.
    |
    */

    'allowed_gyg_supplier_api_hosts' => array_values(array_filter(array_map(
        static fn (string $h): string => strtolower(trim($h)),
        explode(',', (string) env(
            'GYG_SUPPLIER_PLAYGROUND_ALLOWED_HOSTS',
            'supplier-api.getyourguide.com'
        ))
    ))),

];
