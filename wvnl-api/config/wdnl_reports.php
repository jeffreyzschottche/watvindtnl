<?php

return [
    'dev_enabled' => (bool) env('WDNL_DEV_REPORTS_ENABLED', false),
    'dev_model' => env('OPENAI_MODEL', 'gpt-6-luna'),
    'dev_email' => env('WDNL_DEV_REPORT_EMAIL', 'appdemo@mail.com'),
    'openai_key' => env('OPENAI_API_KEY'),
    'deepseek_key' => env('DEEPSEEK_API_KEY'),
    'stripe_secret' => env('STRIPE_SECRET_KEY'),
    'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    // Only bank payments: iDEAL (iDEAL | Wero) by default. Never cards, Klarna or other methods.
    'stripe_payment_methods' => array_values(array_filter(array_map('trim', explode(',', (string) env('STRIPE_PAYMENT_METHODS', 'ideal'))))),
    'web_url' => env('WDNL_WEB_URL', 'http://localhost:3000'),
    // Payments remain closed until deployment, mail/refund procedures and privacy review are complete.
    'sales_enabled' => (bool) env('WDNL_REPORT_SALES_ENABLED', false),
    // Conservative uncached/peak prices, USD per million tokens, verified 2026-11-08.
    'rates' => [
        'gpt-6-luna' => ['input' => .10, 'output' => .50],
        'gpt-6.1-terra' => ['input' => 1, 'output' => 5],
        'gpt-6.1-sol' => ['input' => 2, 'output' => 10],
        'gpt-6-astra' => ['input' => 10, 'output' => 50],
        'deepseek-flash' => ['input' => .30, 'output' => 1.20],
        'deepseek-v4-pro' => ['input' => 1.32, 'output' => 3.96],
    ],
    'models' => [
        'openai' => ['gpt-6-luna', 'gpt-6.1-terra', 'gpt-6.1-sol', 'gpt-6-astra'],
        'deepseek' => ['deepseek-flash', 'deepseek-v4-pro'],
    ],
];
