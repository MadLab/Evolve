<?php

return [
    'prefix' => 'evolve',
    'middleware' => ['web'],
    'admin_emails' => [
        'default@admin.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversion Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, individual conversions are logged with optional model
    | associations for debugging and auditing purposes.
    |
    */
    'log_conversions' => env('EVOLVE_LOG_CONVERSIONS', false),
    'conversion_log_retention_days' => 30,

    'bot_detection' => [
        'enabled' => true,
        'track_separately' => true,
        'user_agent_patterns' => [
            'bot',
            'crawler',
            'spider',
            'googlebot',
            'bingbot',
            'yandexbot',
            'baiduspider',
            'duckduckbot',
            'slurp',
            'facebookexternalhit',
            'linkedinbot',
            'twitterbot',
            'applebot',
            'semrushbot',
            'ahrefsbot',
            'mj12bot',
            'dotbot',
            'petalbot',
            'bytespider',
            'curl',
            'wget',
            'python-requests',
            'java',
            'headless',
            'phantom',
            'selenium',
            'puppeteer',
            'playwright',
        ],
    ],
];
