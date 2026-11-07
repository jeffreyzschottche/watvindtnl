<?php

return [
    'token_days' => (int) env('AUTH_TOKEN_DAYS', 14),
    'limits' => [
        'api' => (int) env('RATE_API_PER_MINUTE', 120),
        'login' => (int) env('RATE_LOGIN_PER_15_MINUTES', 5),
        'registration' => (int) env('RATE_REGISTRATION_PER_HOUR', 3),
        'password-reset' => (int) env('RATE_RESET_PER_HOUR', 3),
        'contact' => (int) env('RATE_CONTACT_PER_HOUR', 3),
        'voting' => (int) env('RATE_VOTES_PER_MINUTE', 20),
        'reports' => (int) env('RATE_REPORTS_PER_HOUR', 2),
        'admin' => (int) env('RATE_ADMIN_PER_MINUTE', 60),
    ],
];
