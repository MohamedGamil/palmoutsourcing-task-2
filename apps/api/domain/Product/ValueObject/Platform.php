<?php

declare(strict_types=1);

namespace Domain\Product\ValueObject;

use Domain\Product\Exception\InvalidPlatformException;

/**
 * Platform Value Object
 * 
 * Represents a supported e-commerce platform.
 * Immutable value object ensuring platform validity.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-003: Domain layer includes value types
 * - REQ-VAL-006: Platform must be one of supported values
 */
final class Platform
{
    private const AMAZON = 'amazon';
    private const JUMIA = 'jumia';

    private const SUPPORTED_PLATFORMS = [
        self::AMAZON,
        self::JUMIA,
    ];

    private const PLATFORM_DOMAINS = [
        self::AMAZON => ['amazon.com', 'amazon.co.uk', 'amazon.de', 'amazon.fr', 'amazon.ca', 'amazon.eg'],
        self::JUMIA => ['jumia.com.eg', 'jumia.com', 'jumia.co.ke', 'jumia.com.ng'],
    ];

    private string $value;

    private function __construct(string $value)
    {
        $normalized = strtolower(trim($value));
        
        if (!in_array($normalized, self::SUPPORTED_PLATFORMS, true)) {
            throw InvalidPlatformException::unsupported($value, self::SUPPORTED_PLATFORMS);
        }

        $this->value = $normalized;
    }

    public static function make(string $value): self
    {
        return new self($value);
    }

    public static function amazon(): self
    {
        return new self(self::AMAZON);
    }

    public static function jumia(): self
    {
        return new self(self::JUMIA);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function isAmazon(): bool
    {
        return $this->value === self::AMAZON;
    }

    public function isJumia(): bool
    {
        return $this->value === self::JUMIA;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function equals(Platform $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get domains associated with this platform
     */
    public function getDomains(): array
    {
        return self::PLATFORM_DOMAINS[$this->value] ?? [];
    }

    /**
     * Check if a URL belongs to this platform
     */
    public function matchesUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        
        if ($host === null || $host === false) {
            return false;
        }

        $domains = $this->getDomains();
        
        foreach ($domains as $domain) {
            if (str_ends_with($host, $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all supported platform names
     */
    public static function getSupportedPlatforms(): array
    {
        return self::SUPPORTED_PLATFORMS;
    }
}
