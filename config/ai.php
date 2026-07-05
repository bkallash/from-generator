<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Gemini Configuration
    |--------------------------------------------------------------------------
    |
    | API key and model settings for Google Gemini AI integration.
    | Uses the Google AI Studio (generativelanguage.googleapis.com) endpoint.
    |
    */

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model'   => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | How long to cache AI-generated insights (in seconds).
    | Default: 12 hour (43200 seconds).
    |
    */

    'cache_ttl' => (int) env('AI_CACHE_TTL', 43200),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */

    'features' => [
        'insights'  => (bool) env('AI_INSIGHTS_ENABLED', true),
        'sentiment' => (bool) env('AI_SENTIMENT_ENABLED', true),
        'anomalies' => (bool) env('AI_ANOMALIES_ENABLED', true),
        'form_builder' => (bool) env('AI_FORM_BUILDER_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    |
    | Max submissions to send to Gemini per request (cost control).
    |
    | Form Builder limit sets maximum chat messages per session.
    |
    */

    'max_submissions_per_request' => (int) env('AI_MAX_SUBMISSIONS', 250),

    'form_builder' => [
        'max_messages_per_session' => (int) env('AI_FORM_BUILDER_MAX_MESSAGES', 50),
        'rate_limit_per_minute'   => (int) env('AI_FORM_BUILDER_RATE_LIMIT', 10),
    ],

];
