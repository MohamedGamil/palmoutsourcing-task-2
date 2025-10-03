<?php

declare(strict_types=1);

namespace Domain\Product\Service;

/**
 * Proxy Service Interface
 * 
 * Defines the contract for proxy management operations.
 * This interface is framework-independent and will be implemented in the application layer.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-006: App layer implements services based on domain layer contracts
 * - REQ-PROXY-001 to REQ-PROXY-003: Proxy service requirements
 */
interface ProxyServiceInterface
{
    /**
     * Get the next available healthy proxy
     * 
     * @return ProxyInfo|null Returns null if no healthy proxies available
     */
    public function getNextProxy(): ?ProxyInfo;

    /**
     * Get all available proxies
     * 
     * @return ProxyInfo[]
     */
    public function getAllProxies(): array;

    /**
     * Check if the proxy service is healthy
     */
    public function isHealthy(): bool;

    /**
     * Get proxy service status information
     */
    public function getStatus(): ProxyServiceStatus;
}
