<?php

return [
    'enabled' => env('MONITORING_ENABLED', true),

    'alerts' => [
        'email' => env('MONITORING_ALERT_EMAIL', null),
        'slack_webhook' => env('MONITORING_SLACK_WEBHOOK', null),
        'enabled' => env('MONITORING_ALERTS_ENABLED', true),
    ],

    'metrics' => [
        'enabled' => env('MONITORING_METRICS_ENABLED', true),
        'retention_hours' => env('MONITORING_METRICS_RETENTION_HOURS', 24),
    ],

    'health_checks' => [
        'enabled' => env('MONITORING_HEALTH_CHECKS_ENABLED', true),
        'interval_seconds' => env('MONITORING_HEALTH_CHECK_INTERVAL', 60),
    ],

    'logging' => [
        'channel' => env('MONITORING_LOG_CHANNEL', 'monitoring'),
        'level' => env('MONITORING_LOG_LEVEL', 'info'),
    ],

    'performance' => [
        'slow_query_threshold_ms' => env('MONITORING_SLOW_QUERY_THRESHOLD', 1000),
        'slow_api_threshold_ms' => env('MONITORING_SLOW_API_THRESHOLD', 2000),
    ],

    'external_services' => [
        'stripe' => [
            'enabled' => env('MONITORING_STRIPE_ENABLED', true),
            'timeout_seconds' => env('MONITORING_STRIPE_TIMEOUT', 30),
        ],
    ],
];