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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
        'scopes' => array_values(array_filter(array_map(
            static fn (string $scope): string => trim($scope),
            explode(',', (string) env('GOOGLE_SCOPES', 'openid,profile,email')),
        ))),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whoop' => [
        'client_id' => env('WHOOP_CLIENT_ID'),
        'client_secret' => env('WHOOP_CLIENT_SECRET'),
        'redirect' => env('WHOOP_REDIRECT_URI'),
        'base_url' => env('WHOOP_BASE_URL', 'https://api.prod.whoop.com'),
        'auth_url' => env('WHOOP_AUTH_URL', 'https://api.prod.whoop.com/oauth/oauth2/auth'),
        'token_url' => env('WHOOP_TOKEN_URL', 'https://api.prod.whoop.com/oauth/oauth2/token'),
        'scopes' => array_values(array_filter(array_map(
            static fn (string $scope): string => trim($scope),
            explode(',', (string) env(
                'WHOOP_SCOPES',
                'offline,read:profile,read:recovery,read:sleep,read:cycles,read:workout,read:body_measurement',
            )),
        ))),
    ],

];
