<?php

declare(strict_types=1);

namespace Domain\Product\Service;

/**
 * Proxy Service Status DTO
 * 
 * Data Transfer Object representing proxy service status.
 */
final class ProxyServiceStatus
{
    private int $totalProxies;
    private int $healthyProxies;
    private bool $isHealthy;
    private string $message;

    public function __construct(
        int $totalProxies,
        int $healthyProxies,
        bool $isHealthy,
        string $message = ''
    ) {
        $this->totalProxies = $totalProxies;
        $this->healthyProxies = $healthyProxies;
        $this->isHealthy = $isHealthy;
        $this->message = $message;
    }

    public function getTotalProxies(): int
    {
        return $this->totalProxies;
    }

    public function getHealthyProxies(): int
    {
        return $this->healthyProxies;
    }

    public function isHealthy(): bool
    {
        return $this->isHealthy;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return [
            'total_proxies' => $this->totalProxies,
            'healthy_proxies' => $this->healthyProxies,
            'is_healthy' => $this->isHealthy,
            'message' => $this->message,
        ];
    }
}
