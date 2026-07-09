<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    // ── Email Providers ──────────────────────────────────────────────────────

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // ── Payment Gateways ─────────────────────────────────────────────────────

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        // Country of the Connect platform account. Recipients in a DIFFERENT
        // country are cross-border and need the `recipient` service agreement;
        // same-country recipients must NOT use it. See StripeConnect::ensureAccount.
        'platform_country' => env('STRIPE_PLATFORM_COUNTRY', 'US'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox | live
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    // Registered payment gateways (driver keys resolved by PaymentManager).
    // Adding a gateway = add a driver class + a key here. No schema changes.
    'payments' => [
        'default' => env('PAYMENTS_DEFAULT', 'stripe'),
        'gateways' => ['stripe', 'paypal'],
    ],

    // Registered payout rails (resolved by PayoutRailManager).
    'payouts' => [
        'default' => env('PAYOUTS_DEFAULT', 'stripe'),
        'rails' => ['stripe'],
    ],

    // ── AI providers ─────────────────────────────────────────────────────────
    // Switch the active provider with AI_PROVIDER (groq | openai | gemini).
    'ai' => [
        'default' => env('AI_PROVIDER', 'groq'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-flash-latest'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    // ── Cloud Storage ────────────────────────────────────────────────────────

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    ],

    // ── Real-time (Reverb / WebSockets) ──────────────────────────────────────

    'reverb' => [
        'app_id' => env('REVERB_APP_ID'),
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'host' => env('REVERB_HOST', 'localhost'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
    ],

];
