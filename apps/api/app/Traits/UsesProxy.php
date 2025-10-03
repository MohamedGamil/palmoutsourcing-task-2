<?php

declare(strict_types=1);

namespace App\Traits;

use App\Facades\ProxyService;
use Domain\Product\Service\ProxyInfo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Uses Proxy Trait
 * 
 * Provides convenient methods for services that need proxy functionality.
 * This trait can be used by scraping services and other HTTP clients.
 * 
 * Requirements Implemented:
 * - REQ-INT-004: System SHALL use proxy rotation for all scraping requests
 * - REQ-INT-005: Failed proxy requests SHALL trigger fallback mechanisms
 */
trait UsesProxy
{
    private ?ProxyInfo $currentProxy = null;
    private int $proxyRetryCount = 0;

    /**
     * Get a proxy for HTTP requests
     * 
     * REQ-INT-004: System SHALL use proxy rotation for all scraping requests
     */
    protected function getProxy(): ?ProxyInfo
    {
        // Check if proxy is enabled via configuration
        if (!$this->isProxyEnabled()) {
            Log::debug('[PROXY-TRAIT] Proxy usage disabled via configuration');
            return null;
        }

        if ($this->currentProxy === null || $this->shouldRotateProxy()) {
            $this->currentProxy = ProxyService::getNextProxy();
            $this->proxyRetryCount = 0;
            
            if ($this->currentProxy) {
                Log::info('[PROXY-TRAIT] Selected proxy for request', [
                    'proxy' => $this->currentProxy->getUrl(),
                    'host' => $this->currentProxy->getHost(),
                    'port' => $this->currentProxy->getPort(),
                ]);
            } else {
                Log::warning('[PROXY-TRAIT] No proxy available');
            }
        }

        return $this->currentProxy;
    }

    /**
     * Mark current proxy as failed and get next one
     * 
     * REQ-INT-005: Failed proxy requests SHALL trigger fallback mechanisms
     */
    protected function rotateProxy(): ?ProxyInfo
    {
        $this->proxyRetryCount++;
        
        Log::info('[PROXY-TRAIT] Rotating proxy due to failure', [
            'previous_proxy' => $this->currentProxy?->getUrl(),
            'retry_count' => $this->proxyRetryCount,
        ]);

        $this->currentProxy = null;
        return $this->getProxy();
    }

    /**
     * Reset proxy state
     */
    protected function resetProxy(): void
    {
        $this->currentProxy = null;
        $this->proxyRetryCount = 0;
        
        Log::debug('[PROXY-TRAIT] Proxy state reset');
    }

    /**
     * Check if we should rotate to the next proxy
     */
    private function shouldRotateProxy(): bool
    {
        $maxRetries = Config::get('scraping.proxy.max_attempts', 3);

        return $this->proxyRetryCount >= $maxRetries;
    }

    /**
     * Check if proxy usage is enabled via configuration
     * 
     * @return bool
     */
    protected function isProxyEnabled(): bool
    {
        return Config::get('scraping.proxy.enabled', true);
    }

    /**
     * Get proxy configuration for HTTP client
     * 
     * Returns array suitable for Guzzle HTTP client proxy configuration
     * 
     * @return array|null
     */
    protected function getProxyConfig(): ?array
    {
        $proxy = $this->getProxy();
        
        if (!$proxy) {
            return null;
        }

        $timeout = Config::get('scraping.proxy.timeout', 30);
        $connectTimeout = Config::get('scraping.request.connect_timeout', 10);

        return [
            'proxy' => $proxy->getUrl(),
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
        ];
    }

    /**
     * Log proxy usage statistics
     */
    protected function logProxyStats(): void
    {
        if (!$this->isProxyEnabled()) {
            Log::info('[PROXY-TRAIT] Proxy usage disabled - no proxy statistics available');
            return;
        }

        $status = ProxyService::getStatus();
        
        Log::info('[PROXY-TRAIT] Proxy service statistics', [
            'proxy_enabled' => true,
            'total_proxies' => $status->getTotalProxies(),
            'healthy_proxies' => $status->getHealthyProxies(),
            'service_healthy' => $status->isHealthy(),
            'current_proxy' => $this->currentProxy?->getUrl(),
            'retry_count' => $this->proxyRetryCount,
        ]);
    }

    /**
     * Get HTTP client options with or without proxy based on configuration
     * 
     * @param array $additionalOptions Additional HTTP client options
     * @return array Complete HTTP client options
     */
    protected function getHttpClientOptions(array $additionalOptions = []): array
    {
        $options = [
            'timeout' => Config::get('scraping.request.timeout', 60),
            'connect_timeout' => Config::get('scraping.request.connect_timeout', 10),
            'headers' => [
                'User-Agent' => $this->getUserAgent(),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ],
        ];

        // Add proxy configuration if enabled
        if ($this->isProxyEnabled()) {
            $proxyConfig = $this->getProxyConfig();
            if ($proxyConfig) {
                $options = array_merge($options, $proxyConfig);
                Log::debug('[PROXY-TRAIT] HTTP client configured with proxy', [
                    'proxy' => $proxyConfig['proxy'],
                ]);
            }
        } else {
            Log::debug('[PROXY-TRAIT] HTTP client configured without proxy');
        }

        return array_merge($options, $additionalOptions);
    }

    /**
     * Get user agent for requests
     * 
     * @return string
     */
    private function getUserAgent(): string
    {
        $userAgents = Config::get('scraping.user_agent.pool', [
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
        ]);

        $default = Config::get('scraping.user_agent.default', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        if (Config::get('scraping.user_agent.rotation_enabled', true) && !empty($userAgents)) {
            return $userAgents[array_rand($userAgents)];
        }

        return $default;
    }
}