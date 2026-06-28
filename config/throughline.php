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
                'enabled' => (bool) env('APPLE_AUTH_ENABLED', false),
                'headline' => 'Continue with Apple',
                'description' => 'Live when Apple web sign-in credentials are configured and enabled.',
            ],
            'phone' => [
                'enabled' => (bool) env('PHONE_AUTH_ENABLED', false),
                'headline' => 'Sign up with phone',
                'description' => 'Live when OTP delivery is configured. It uses one-time codes instead of passwords.',
            ],
        ],
        'phone' => [
            'enabled' => (bool) env('PHONE_AUTH_ENABLED', false),
            'driver' => env('PHONE_AUTH_DRIVER', 'log'),
            'otp_digits' => (int) env('PHONE_AUTH_OTP_DIGITS', 6),
            'ttl_minutes' => (int) env('PHONE_AUTH_TTL_MINUTES', 10),
            'max_attempts' => (int) env('PHONE_AUTH_MAX_ATTEMPTS', 5),
            'twilio' => [
                'account_sid' => env('TWILIO_ACCOUNT_SID'),
                'auth_token' => env('TWILIO_AUTH_TOKEN'),
                'from' => env('TWILIO_FROM_NUMBER'),
                'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
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
    'billing' => [
        'provider' => 'stripe',
        'currency' => env('THROUGHLINE_BILLING_CURRENCY', 'USD'),
        'stripe' => [
            'secret_key' => env('STRIPE_SECRET'),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
    ],
    'integrations' => [
        'whoop' => [
            'lookback_days' => (int) env('WHOOP_SYNC_LOOKBACK_DAYS', 10),
            'webhook_secret' => env('WHOOP_WEBHOOK_SECRET'),
            'webhook_tolerance_seconds' => (int) env('WHOOP_WEBHOOK_TOLERANCE_SECONDS', 300),
            'webhook_lookback_days' => (int) env('WHOOP_WEBHOOK_LOOKBACK_DAYS', 14),
            'capabilities' => [
                'Recovery score, resting heart rate, HRV, blood oxygen, and skin temperature.',
                'Sleep duration, REM sleep, slow-wave sleep, respiratory rate, and sleep performance.',
                'Daily strain, workout distance, workout duration, and cycle-level training load.',
            ],
        ],
    ],
];
