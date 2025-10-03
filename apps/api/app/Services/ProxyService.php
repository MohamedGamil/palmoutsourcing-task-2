<?php

declare(strict_types=1);

namespace App\Services;

use Domain\Product\Service\ProxyInfo;
use Domain\Product\Service\ProxyServiceInterface;
use Domain\Product\Service\ProxyServiceStatus;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Proxy Service Implementation
 * 
 * Concrete implementation of ProxyServiceInterface that communicates with the Golang proxy service.
 * 
 * Requirements Implemented:
 * - REQ-INT-001: Backend SHALL communicate with Golang proxy service
 * - REQ-INT-002: System SHALL retrieve active proxies from Golang service
 * - REQ-INT-003: Integration SHALL handle proxy service unavailability
 * - REQ-INT-004: System SHALL use proxy rotation for all scraping requests
 * - REQ-INT-005: Failed proxy requests SHALL trigger fallback mechanisms
 * - REQ-ARCH-006: App layer SHALL implement services based on domain layer contracts
 * - REQ-GO-API-001: Service SHALL expose endpoint /proxy/next
 * - REQ-GO-API-002: Service SHALL return JSON response with proxy details
 */
class ProxyService implements ProxyServiceInterface
{
    private const DEFAULT_TIMEOUT = 10; // seconds
    private const DEFAULT_RETRIES = 3;
    private const CACHE_TTL = 60; // seconds

    private HttpClient $httpClient;
    private string $proxyServiceUrl;
    private int $timeout;
    private int $maxRetries;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->proxyServiceUrl = $this->buildProxyServiceUrl();
        $this->timeout = (int) config('proxy.timeout', self::DEFAULT_TIMEOUT);
        $this->maxRetries = (int) config('proxy.max_retries', self::DEFAULT_RETRIES);

        Log::info('[PROXY-SERVICE] Initialized', [
            'url' => $this->proxyServiceUrl,
            'timeout' => $this->timeout,
            'max_retries' => $this->maxRetries,
        ]);
    }

    /**
     * Get the next available healthy proxy
     * 
     * REQ-INT-002: System SHALL retrieve active proxies from Golang service
     * REQ-GO-API-001: Service SHALL expose endpoint /proxy/next
     * 
     * @return ProxyInfo|null Returns null if no healthy proxies available
     */
    public function getNextProxy(): ?ProxyInfo
    {
        Log::info('[PROXY-SERVICE] Requesting next proxy');

        try {
            $response = $this->makeRequest('/proxy/next');
            
            if (!$response->successful()) {
                Log::warning('[PROXY-SERVICE] Failed to get next proxy', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            
            if (!$this->isValidProxyResponse($data)) {
                Log::warning('[PROXY-SERVICE] Invalid proxy response format', [
                    'data' => $data,
                ]);
                return null;
            }

            $proxyInfo = $this->parseProxyResponse($data);
            
            Log::info('[PROXY-SERVICE] Successfully retrieved next proxy', [
                'proxy' => $proxyInfo->getUrl(),
                'is_healthy' => $proxyInfo->isHealthy(),
            ]);

            return $proxyInfo;

        } catch (Exception $e) {
            Log::error('[PROXY-SERVICE] Exception getting next proxy', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get all available proxies
     * 
     * REQ-INT-002: System SHALL retrieve active proxies from Golang service
     * REQ-PERF-004: System SHALL implement caching for frequently accessed data
     * REQ-INT-005: Failed proxy requests SHALL trigger fallback mechanisms
     * 
     * @return ProxyInfo[]
     */
    public function getAllProxies(): array
    {
        $cacheKey = 'proxy_service:all_proxies';
        $cacheTtl = (int) config('proxy.cache_ttl', self::CACHE_TTL);
        $cacheEnabled = (bool) config('proxy.cache_enabled', true);

        // Try to get from cache first
        if ($cacheEnabled) {
            $cachedProxies = Cache::get($cacheKey);
            if ($cachedProxies !== null) {
                Log::debug('[PROXY-SERVICE] Returned cached proxies', [
                    'count' => count($cachedProxies),
                ]);
                return $this->deserializeProxies($cachedProxies);
            }
        }

        Log::info('[PROXY-SERVICE] Requesting all proxies from service');

        try {
            $response = $this->makeRequest('/proxies');
            
            if (!$response->successful()) {
                Log::warning('[PROXY-SERVICE] Failed to get all proxies', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return $this->getFallbackProxies();
            }

            $data = $response->json();
            
            if (!isset($data['proxies']) || !is_array($data['proxies'])) {
                Log::warning('[PROXY-SERVICE] Invalid proxies response format', [
                    'data' => $data,
                ]);
                return $this->getFallbackProxies();
            }

            $proxies = [];
            foreach ($data['proxies'] as $proxyData) {
                if ($this->isValidProxyData($proxyData)) {
                    $proxies[] = $this->parseProxyData($proxyData);
                }
            }

            // Cache the results
            if ($cacheEnabled && !empty($proxies)) {
                Cache::put($cacheKey, $this->serializeProxies($proxies), $cacheTtl);
            }

            Log::info('[PROXY-SERVICE] Successfully retrieved all proxies', [
                'count' => count($proxies),
            ]);

            return $proxies;

        } catch (Exception $e) {
            Log::error('[PROXY-SERVICE] Exception getting all proxies', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->getFallbackProxies();
        }
    }

    /**
     * Check if the proxy service is healthy
     * 
     * REQ-INT-003: Integration SHALL handle proxy service unavailability
     */
    public function isHealthy(): bool
    {
        Log::debug('[PROXY-SERVICE] Checking service health');

        try {
            $response = $this->makeRequest('/health');
            
            $isHealthy = $response->successful();
            
            Log::info('[PROXY-SERVICE] Health check result', [
                'is_healthy' => $isHealthy,
                'status' => $response->status(),
            ]);

            return $isHealthy;

        } catch (Exception $e) {
            Log::error('[PROXY-SERVICE] Health check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get proxy service status information
     * 
     * REQ-INT-003: Integration SHALL handle proxy service unavailability
     */
    public function getStatus(): ProxyServiceStatus
    {
        Log::debug('[PROXY-SERVICE] Getting service status');

        try {
            $response = $this->makeRequest('/health');
            
            if (!$response->successful()) {
                return new ProxyServiceStatus(
                    totalProxies: 0,
                    healthyProxies: 0,
                    isHealthy: false,
                    message: "Proxy service unavailable (HTTP {$response->status()})"
                );
            }

            $data = $response->json();
            $stats = $data['stats'] ?? [];

            $status = new ProxyServiceStatus(
                totalProxies: (int) ($stats['total_proxies'] ?? 0),
                healthyProxies: (int) ($stats['healthy_proxies'] ?? 0),
                isHealthy: true,
                message: $data['status'] ?? 'healthy'
            );

            Log::info('[PROXY-SERVICE] Service status retrieved', $status->toArray());

            return $status;

        } catch (Exception $e) {
            Log::error('[PROXY-SERVICE] Exception getting service status', [
                'error' => $e->getMessage(),
            ]);

            return new ProxyServiceStatus(
                totalProxies: 0,
                healthyProxies: 0,
                isHealthy: false,
                message: "Service communication error: {$e->getMessage()}"
            );
        }
    }

    /**
     * Make HTTP request to proxy service with retries
     * 
     * REQ-INT-005: Failed proxy requests SHALL trigger fallback mechanisms
     * REQ-REL-006: System SHALL implement retry mechanisms for failed operations
     */
    private function makeRequest(string $endpoint): Response
    {
        $url = $this->proxyServiceUrl . $endpoint;
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                Log::debug('[PROXY-SERVICE] Making request', [
                    'url' => $url,
                    'attempt' => $attempt,
                    'max_attempts' => $this->maxRetries,
                ]);

                $response = $this->httpClient
                    ->timeout($this->timeout)
                    ->get($url);

                if ($response->successful()) {
                    return $response;
                }

                Log::warning('[PROXY-SERVICE] Request failed', [
                    'url' => $url,
                    'attempt' => $attempt,
                    'status' => $response->status(),
                ]);

                // Don't retry on client errors (4xx), only on server errors (5xx) and timeouts
                if ($response->status() < 500) {
                    return $response;
                }

            } catch (RequestException $e) {
                $lastException = $e;
                
                Log::warning('[PROXY-SERVICE] Request exception', [
                    'url' => $url,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $this->maxRetries) {
                    // Exponential backoff: 1s, 2s, 4s
                    $delay = pow(2, $attempt - 1);
                    sleep($delay);
                }
            }
        }

        // If we get here, all retries failed
        if ($lastException) {
            throw $lastException;
        }

        throw new Exception('All retry attempts failed');
    }

    /**
     * Build the proxy service URL from environment configuration
     */
    private function buildProxyServiceUrl(): string
    {
        $host = config('proxy.service_host', 'proxy-service');
        $port = config('proxy.service_port', '7001');
        $protocol = config('proxy.service_protocol', 'http');

        // Check environment variable first (Docker environment)
        if ($envPort = env('PROXY_SERVICE_PORT')) {
            $port = $envPort;
        }

        $url = "{$protocol}://{$host}:{$port}";
        
        Log::debug('[PROXY-SERVICE] Built service URL', ['url' => $url]);
        
        return $url;
    }

    /**
     * Validate proxy response from /proxy/next endpoint
     */
    private function isValidProxyResponse(array $data): bool
    {
        return isset($data['proxy']) && 
               is_string($data['proxy']) && 
               !empty($data['proxy']);
    }

    /**
     * Parse proxy response from /proxy/next endpoint
     */
    private function parseProxyResponse(array $data): ProxyInfo
    {
        // Parse "host:port" format
        $proxyParts = explode(':', $data['proxy']);
        $host = $proxyParts[0] ?? '';
        $port = (int) ($proxyParts[1] ?? 80);

        return new ProxyInfo(
            host: $host,
            port: $port,
            isHealthy: $data['is_healthy'] ?? true,
            lastChecked: $data['last_checked'] ?? null
        );
    }

    /**
     * Validate individual proxy data from /proxies endpoint
     */
    private function isValidProxyData(array $data): bool
    {
        return isset($data['host']) && 
               isset($data['port']) && 
               is_string($data['host']) && 
               is_numeric($data['port']) &&
               !empty($data['host']);
    }

    /**
     * Parse individual proxy data from /proxies endpoint
     */
    private function parseProxyData(array $data): ProxyInfo
    {
        return new ProxyInfo(
            host: $data['host'],
            port: (int) $data['port'],
            isHealthy: $data['is_healthy'] ?? true,
            lastChecked: $data['last_checked'] ?? null
        );
    }

    /**
     * Get fallback proxies when service is unavailable
     * 
     * REQ-INT-005: Failed proxy requests SHALL trigger fallback mechanisms
     * 
     * @return ProxyInfo[]
     */
    private function getFallbackProxies(): array
    {
        $fallbackEnabled = (bool) config('proxy.fallback_enabled', true);
        
        if (!$fallbackEnabled) {
            Log::info('[PROXY-SERVICE] Fallback disabled, returning empty array');
            return [];
        }

        $fallbackProxies = config('proxy.fallback_proxies', []);
        
        if (empty($fallbackProxies)) {
            Log::warning('[PROXY-SERVICE] No fallback proxies configured');
            return [];
        }

        $proxies = [];
        foreach ($fallbackProxies as $proxyString) {
            $parts = explode(':', $proxyString);
            if (count($parts) === 2) {
                $proxies[] = new ProxyInfo(
                    host: $parts[0],
                    port: (int) $parts[1],
                    isHealthy: true,
                    lastChecked: null
                );
            }
        }

        Log::info('[PROXY-SERVICE] Using fallback proxies', [
            'count' => count($proxies),
        ]);

        return $proxies;
    }

    /**
     * Serialize proxy objects for caching
     * 
     * @param ProxyInfo[] $proxies
     * @return array
     */
    private function serializeProxies(array $proxies): array
    {
        return array_map(function (ProxyInfo $proxy) {
            return $proxy->toArray();
        }, $proxies);
    }

    /**
     * Deserialize proxy data from cache
     * 
     * @param array $cachedData
     * @return ProxyInfo[]
     */
    private function deserializeProxies(array $cachedData): array
    {
        return array_map(function (array $data) {
            return new ProxyInfo(
                host: $data['host'],
                port: $data['port'],
                isHealthy: $data['is_healthy'] ?? true,
                lastChecked: $data['last_checked'] ?? null
            );
        }, $cachedData);
    }
}