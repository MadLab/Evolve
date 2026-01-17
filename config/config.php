<?php

return [
    'prefix' => 'evolve',
    'middleware' => ['web'],
    'admin_emails' => [
        'default@admin.com',
    ],

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
