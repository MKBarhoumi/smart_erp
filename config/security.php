<?php

return [
    'force_https' => env('FORCE_HTTPS', false),

    'secure_headers' => [
        'enabled' => env('SECURE_HEADERS_ENABLED', true),
        'x_frame_options' => env('SECURE_X_FRAME_OPTIONS', 'SAMEORIGIN'),
        'x_xss_protection' => env('SECURE_X_XSS_PROTECTION', '1; mode=block'),
        'x_content_type_options' => env('SECURE_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'referrer_policy' => env('SECURE_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'strict_transport_security' => env('SECURE_STRICT_TRANSPORT_SECURITY', 'max-age=31536000; includeSubDomains'),
    ],

    'cookies' => [
        'secure' => env('COOKIE_SECURE', false),
        'same_site' => env('COOKIE_SAME_SITE', 'lax'),
        'http_only' => env('COOKIE_HTTP_ONLY', true),
    ],

    'input_sanitization' => [
        'enabled' => env('INPUT_SANITIZATION_ENABLED', true),
        'except' => explode(',', env('INPUT_SANITIZATION_EXCEPT', 'password,password_confirmation,current_password,content,description,bio')),
    ],

    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),
        'global_limit' => env('RATE_LIMIT_GLOBAL', 60),
        'tenant_limit' => env('RATE_LIMIT_TENANT', 100),
        'decay_minutes' => env('RATE_LIMIT_DECAY_MINUTES', 1),
    ],

    'audit_logging' => [
        'enabled' => env('SECURITY_AUDIT_LOGGING_ENABLED', true),
        'channel' => env('SECURITY_AUDIT_LOG_CHANNEL', 'security'),
        'log_all_requests' => env('SECURITY_LOG_ALL_REQUESTS', false),
    ],

    'sensitive_data' => [
        'hide_in_logs' => env('SECURITY_HIDE_SENSITIVE_DATA', true),
        'hide_in_responses' => env('SECURITY_HIDE_SENSITIVE_RESPONSES', true),
    ],

    'encryption' => [
        'key' => env('APP_KEY'),
        'cipher' => env('APP_CIPHER', 'AES-256-CBC'),
    ],
];