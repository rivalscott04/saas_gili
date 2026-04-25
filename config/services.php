<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'getyourguide' => [
        'base_url' => env('GYG_BASE_URL', 'https://api.getyourguide.com'),
        'timeout_seconds' => (int) env('GYG_TIMEOUT_SECONDS', 30),
        'retry_max' => (int) env('GYG_RETRY_MAX', 3),
        'sync_via_queue' => filter_var(env('GYG_SYNC_VIA_QUEUE', true), FILTER_VALIDATE_BOOLEAN),
        'job_tries' => (int) env('GYG_JOB_TRIES', 5),
        'job_backoff' => array_values(array_filter(array_map('intval', explode(',', (string) env('GYG_JOB_BACKOFF', '15,60,300'))))),
    ],

];
