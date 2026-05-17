<?php

return [
    'client_id' => env('AIRBNB_CLIENT_ID', ''),
    'client_secret' => env('AIRBNB_CLIENT_SECRET', ''),
    'redirect_uri' => env('AIRBNB_REDIRECT_URI', ''),
    'webhook_secret' => env('AIRBNB_WEBHOOK_SECRET', ''),

    'authorize_url' => env('AIRBNB_AUTHORIZE_URL', 'https://www.airbnb.com/oauth2/auth'),
    'token_url' => env('AIRBNB_TOKEN_URL', 'https://api.airbnb.com/v2/oauth2/access_token'),
    'api_base_url' => env('AIRBNB_API_BASE_URL', 'https://api.airbnb.com/v2'),
    'scopes' => env('AIRBNB_OAUTH_SCOPES', 'activities_read reservations_read'),

    'timeout_seconds' => (int) env('AIRBNB_TIMEOUT_SECONDS', 30),
    'sync_via_queue' => filter_var(env('AIRBNB_SYNC_VIA_QUEUE', true), FILTER_VALIDATE_BOOLEAN),
    'job_tries' => (int) env('AIRBNB_JOB_TRIES', 3),
    'job_backoff' => array_values(array_filter(array_map('intval', explode(',', (string) env('AIRBNB_JOB_BACKOFF', '30,120'))))),

    'webhook_signature_header' => env('AIRBNB_WEBHOOK_SIGNATURE_HEADER', 'X-Airbnb-Signature'),
];
