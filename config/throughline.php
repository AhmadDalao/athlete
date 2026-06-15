<?php

return [
    'auth' => [
        'signup_methods' => [
            'email' => [
                'enabled' => true,
                'headline' => 'Email and password',
                'description' => 'Live now and stable for the MVP.',
            ],
            'google' => [
                'enabled' => (bool) env('GOOGLE_AUTH_ENABLED', false),
                'headline' => 'Continue with Google',
                'description' => 'Live when Google OAuth credentials are configured and enabled.',
            ],
            'apple' => [
                'enabled' => false,
                'headline' => 'Continue with Apple',
                'description' => 'Planned after Apple web sign-in, domain verification, and production cert setup.',
            ],
            'phone' => [
                'enabled' => false,
                'headline' => 'Sign up with phone',
                'description' => 'Planned after OTP delivery, fraud throttling, and phone verification support.',
            ],
        ],
    ],
    'metrics' => [
        'dashboard_window_days' => 7,
    ],
    'progress' => [
        'dashboard_window_days' => 14,
    ],
    'api' => [
        'version' => 'v1',
        'token_ttl_days' => (int) env('THROUGHLINE_API_TOKEN_TTL_DAYS', 30),
    ],
    'integrations' => [
        'whoop' => [
            'lookback_days' => (int) env('WHOOP_SYNC_LOOKBACK_DAYS', 10),
            'capabilities' => [
                'Recovery score, resting heart rate, HRV, blood oxygen, and skin temperature.',
                'Sleep duration, REM sleep, slow-wave sleep, respiratory rate, and sleep performance.',
                'Daily strain, workout distance, workout duration, and cycle-level training load.',
            ],
        ],
    ],
];
