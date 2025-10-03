<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scraping Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the scraping service subsystem including
    | proxy settings, timeouts, retry policies, and platform-specific options.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Proxy Configuration
    |--------------------------------------------------------------------------
    |
    | Controls proxy usage for scraping requests. When enabled, all scraping
    | requests will be routed through the configured proxy service.
    |
    */
    'proxy' => [
        'enabled' => env('SCRAPING_PROXY_ENABLED', true),
        'service_url' =>  env('PROXY_SERVICE_PROTOCOL', 'http') . '://' . env('PROXY_SERVICE_HOST', 'proxy-service') . ':' . env('PROXY_SERVICE_PORT', '7001'),
        'timeout' => env('SCRAPING_PROXY_TIMEOUT', 30),
        'connect_timeout' => env('SCRAPING_PROXY_CONNECT_TIMEOUT', 10),
        'retry_attempts' => env('SCRAPING_PROXY_RETRY_ATTEMPTS', 3),
        'max_attempts' => env('SCRAPING_PROXY_MAX_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Configuration
    |--------------------------------------------------------------------------
    |
    | General HTTP request settings for scraping operations.
    |
    */
    'request' => [
        'timeout' => env('SCRAPING_REQUEST_TIMEOUT', 60),
        'connect_timeout' => env('SCRAPING_CONNECT_TIMEOUT', 10),
        'retry_attempts' => env('SCRAPING_RETRY_ATTEMPTS', 3),
        'max_attempts' => env('SCRAPING_REQUEST_MAX_ATTEMPTS', 3),
        'retry_delay' => env('SCRAPING_RETRY_DELAY', 1), // seconds
        'max_redirects' => env('SCRAPING_MAX_REDIRECTS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Agent Configuration
    |--------------------------------------------------------------------------
    |
    | User agent rotation settings for avoiding bot detection.
    |
    */
    'user_agent' => [
        'rotation_enabled' => env('SCRAPING_USER_AGENT_ROTATION', true),
        'default' => env('SCRAPING_DEFAULT_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
        'pool' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 13_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPad; CPU OS 13_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:77.0) Gecko/20100101 Firefox/77.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:77.0) Gecko/20100101 Firefox/77.0',
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration to avoid overwhelming target servers.
    |
    */
    'rate_limiting' => [
        'enabled' => env('SCRAPING_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('SCRAPING_REQUESTS_PER_MINUTE', 60),
        'delay_between_requests' => env('SCRAPING_DELAY_BETWEEN_REQUESTS', 1), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform-Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options specific to each supported platform.
    |
    */
    'platforms' => [
        'amazon' => [
            'enabled' => env('SCRAPING_AMAZON_ENABLED', true),
            'timeout' => env('SCRAPING_AMAZON_TIMEOUT', 45),
            'retry_attempts' => env('SCRAPING_AMAZON_RETRY_ATTEMPTS', 5),
            'max_attempts' => env('SCRAPING_AMAZON_MAX_ATTEMPTS', 5),
            'user_agent_required' => true,
            'proxy_recommended' => true,
        ],
        'jumia' => [
            'enabled' => env('SCRAPING_JUMIA_ENABLED', true),
            'timeout' => env('SCRAPING_JUMIA_TIMEOUT', 30),
            'retry_attempts' => env('SCRAPING_JUMIA_RETRY_ATTEMPTS', 3),
            'max_attempts' => env('SCRAPING_JUMIA_MAX_ATTEMPTS', 3),
            'user_agent_required' => true,
            'proxy_recommended' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Caching settings for scraped data and service responses.
    |
    */
    'cache' => [
        'enabled' => env('SCRAPING_CACHE_ENABLED', true),
        'ttl' => env('SCRAPING_CACHE_TTL', 3600), // 1 hour
        'prefix' => env('SCRAPING_CACHE_PREFIX', 'scraping:'),
        'store' => env('SCRAPING_CACHE_STORE', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Logging settings for scraping operations.
    |
    */
    'logging' => [
        'enabled' => env('SCRAPING_LOGGING_ENABLED', true),
        'level' => env('SCRAPING_LOG_LEVEL', 'info'),
        'channel' => env('SCRAPING_LOG_CHANNEL', 'stack'),
        'log_requests' => env('SCRAPING_LOG_REQUESTS', true),
        'log_responses' => env('SCRAPING_LOG_RESPONSES', false), // Can be verbose
        'log_errors' => env('SCRAPING_LOG_ERRORS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for validating scraped data quality and completeness.
    |
    */
    'validation' => [
        'strict_mode' => env('SCRAPING_STRICT_VALIDATION', false),
        'required_fields' => ['title', 'price'],
        'min_title_length' => env('SCRAPING_MIN_TITLE_LENGTH', 3),
        'max_title_length' => env('SCRAPING_MAX_TITLE_LENGTH', 500),
        'min_price' => env('SCRAPING_MIN_PRICE', 0.01),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for handling errors and failures during scraping.
    |
    */
    'error_handling' => [
        'log_errors' => env('SCRAPING_LOG_ERRORS', true),
        'log_level' => env('SCRAPING_LOG_LEVEL', 'error'),
        'retry_on_rate_limit' => env('SCRAPING_RETRY_ON_RATE_LIMIT', true),
        'max_consecutive_failures' => env('SCRAPING_MAX_CONSECUTIVE_FAILURES', 5),
        'failure_backoff_multiplier' => env('SCRAPING_FAILURE_BACKOFF_MULTIPLIER', 2),
        'circuit_breaker_enabled' => env('SCRAPING_CIRCUIT_BREAKER_ENABLED', true),
        'circuit_breaker_threshold' => env('SCRAPING_CIRCUIT_BREAKER_THRESHOLD', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for controlling request rate to avoid being blocked.
    |
    */
    'rate_limiting' => [
        'enabled' => env('SCRAPING_RATE_LIMITING_ENABLED', true),
        'max_requests_per_minute' => env('SCRAPING_MAX_REQUESTS_PER_MINUTE', 30),
        'delay_between_requests_ms' => env('SCRAPING_DELAY_BETWEEN_REQUESTS_MS', 2000),
        'burst_limit' => env('SCRAPING_BURST_LIMIT', 5),
    ],

];