<?php

declare(strict_types=1);

namespace App\Traits;

use App\Facades\ProxyService;
use Domain\Product\Service\ProxyInfo;
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
        $maxRetries = 3; // MAX_PROXY_RETRIES
        return $this->proxyRetryCount >= $maxRetries;
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

        return [
            'proxy' => $proxy->getUrl(),
            'timeout' => 30,
            'connect_timeout' => 10,
        ];
    }

    /**
     * Log proxy usage statistics
     */
    protected function logProxyStats(): void
    {
        $status = ProxyService::getStatus();
        
        Log::info('[PROXY-TRAIT] Proxy service statistics', [
            'total_proxies' => $status->getTotalProxies(),
            'healthy_proxies' => $status->getHealthyProxies(),
            'service_healthy' => $status->isHealthy(),
            'current_proxy' => $this->currentProxy?->getUrl(),
            'retry_count' => $this->proxyRetryCount,
        ]);
    }
}