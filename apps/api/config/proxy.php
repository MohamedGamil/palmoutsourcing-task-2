<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Proxy Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Golang proxy service integration.
    | These settings control how the Laravel backend communicates with
    | the proxy management microservice.
    |
    */

    /**
     * Proxy service connection settings
     * 
     * REQ-INT-001: Backend SHALL communicate with Golang proxy service
     */
    'service_host' => env('PROXY_SERVICE_HOST', 'proxy-service'),
    'service_port' => env('PROXY_SERVICE_PORT', '7001'),
    'service_protocol' => env('PROXY_SERVICE_PROTOCOL', 'http'),

    /**
     * HTTP client settings
     * 
     * REQ-REL-006: System SHALL implement retry mechanisms for failed operations
     */
    'timeout' => env('PROXY_SERVICE_TIMEOUT', 10), // seconds
    'max_retries' => env('PROXY_SERVICE_MAX_RETRIES', 3),

    /**
     * Caching settings
     * 
     * REQ-PERF-004: System SHALL implement caching for frequently accessed data
     */
    'cache_ttl' => env('PROXY_SERVICE_CACHE_TTL', 60), // seconds
    'cache_enabled' => env('PROXY_SERVICE_CACHE_ENABLED', true),

    /**
     * Fallback settings
     * 
     * REQ-INT-005: Failed proxy requests SHALL trigger fallback mechanisms
     */
    'fallback_enabled' => env('PROXY_SERVICE_FALLBACK_ENABLED', true),
    'fallback_proxies' => [
        // Default fallback proxies in case service is unavailable
        // Format: 'host:port'
        // Example: '127.0.0.1:8888',
    ],
];