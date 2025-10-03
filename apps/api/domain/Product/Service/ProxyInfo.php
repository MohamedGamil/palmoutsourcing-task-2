<?php

declare(strict_types=1);

namespace Domain\Product\Service;

/**
 * Proxy Info DTO
 * 
 * Data Transfer Object representing proxy information.
 */
final class ProxyInfo
{
    private string $host;
    private int $port;
    private bool $isHealthy;
    private ?string $lastChecked;

    public function __construct(
        string $host,
        int $port,
        bool $isHealthy = true,
        ?string $lastChecked = null
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->isHealthy = $isHealthy;
        $this->lastChecked = $lastChecked;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function isHealthy(): bool
    {
        return $this->isHealthy;
    }

    public function getLastChecked(): ?string
    {
        return $this->lastChecked;
    }

    public function getUrl(): string
    {
        return "http://{$this->host}:{$this->port}";
    }

    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'is_healthy' => $this->isHealthy,
            'last_checked' => $this->lastChecked,
            'url' => $this->getUrl(),
        ];
    }
}
