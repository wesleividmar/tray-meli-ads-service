<?php

return [
    'seller_id' => (int) env('MELI_SELLER_ID', 252254392),
    'base_url' => env('MELI_BASE_URL', 'http://mockoon:3001'),
    'site' => env('MELI_SITE', 'MLB'),

    'timeout' => (int) env('MELI_TIMEOUT', 10),
    'connect_timeout' => (int) env('MELI_CONNECT_TIMEOUT', 3),

    'retries' => (int) env('MELI_RETRIES', 2),
    'retry_sleep_ms' => (int) env('MELI_RETRY_SLEEP_MS', 250),

    'user_agent' => env('MELI_USER_AGENT', 'tray-meli-ads-service/1.0'),

    // throttle / rate-limit (Redis)
    'rate_limit_per_second' => (int) env('MELI_RATE_LIMIT_PER_SECOND', 5),
    'throttle_block_seconds' => (int) env('MELI_THROTTLE_BLOCK_SECONDS', 2),
    'throttle_key' => env('MELI_THROTTLE_KEY', 'meli-api'),
];