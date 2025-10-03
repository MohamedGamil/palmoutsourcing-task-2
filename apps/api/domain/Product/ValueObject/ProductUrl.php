<?php

declare(strict_types=1);

namespace Domain\Product\ValueObject;

use Domain\Product\Exception\InvalidProductUrlException;

/**
 * ProductUrl Value Object
 * 
 * Represents a validated product URL from supported e-commerce platforms.
 * Immutable value object ensuring URL validity and format.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-003: Domain layer includes value types
 * - REQ-VAL-002: Product URL must be valid and match platform
 * - REQ-VAL-004: Product URL max 500 characters
 */
final class ProductUrl
{
    private const MAX_LENGTH = 500;

    private string $url;

    private function __construct(string $url)
    {
        $this->validate($url);
        $this->url = $url;
    }

    public static function fromString(string $url): self
    {
        return new self($url);
    }

    private function validate(string $url): void
    {
        // Check not empty
        if (empty(trim($url))) {
            throw InvalidProductUrlException::empty();
        }

        // Check length
        if (mb_strlen($url) > self::MAX_LENGTH) {
            throw InvalidProductUrlException::tooLong($url, self::MAX_LENGTH);
        }

        // Check valid URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw InvalidProductUrlException::invalidFormat($url);
        }

        // Check has HTTP/HTTPS scheme
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw InvalidProductUrlException::invalidScheme($url, $scheme);
        }

        // Check has host
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            throw InvalidProductUrlException::missingHost($url);
        }
    }

    /**
     * Check if URL matches a given platform
     */
    public function matchesPlatform(Platform $platform): bool
    {
        return $platform->matchesUrl($this->url);
    }

    /**
     * Get the domain from the URL
     */
    public function getDomain(): string
    {
        $host = parse_url($this->url, PHP_URL_HOST);
        return $host ?: '';
    }

    /**
     * Get the scheme (http/https)
     */
    public function getScheme(): string
    {
        $scheme = parse_url($this->url, PHP_URL_SCHEME);
        return $scheme ?: '';
    }

    /**
     * Check if URL uses HTTPS
     */
    public function isSecure(): bool
    {
        return $this->getScheme() === 'https';
    }

    public function toString(): string
    {
        return $this->url;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function equals(ProductUrl $other): bool
    {
        return $this->url === $other->url;
    }

    /**
     * Normalize URL for comparison (remove tracking params, etc.)
     * This is useful for detecting duplicate products
     */
    public function toNormalized(): string
    {
        $parsed = parse_url($this->url);
        
        if ($parsed === false) {
            return $this->url;
        }

        $normalized = $parsed['scheme'] . '://' . $parsed['host'];
        
        if (isset($parsed['path'])) {
            $normalized .= $parsed['path'];
        }

        // Keep only essential query parameters (product ID, etc.)
        // Remove tracking parameters (utm_, ref, etc.)
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);
            
            // Filter out common tracking parameters
            $trackingParams = ['utm_source', 'utm_medium', 'utm_campaign', 'ref', 'referrer', 'fbclid', 'gclid'];
            foreach ($trackingParams as $trackingParam) {
                unset($params[$trackingParam]);
            }

            if (!empty($params)) {
                $normalized .= '?' . http_build_query($params);
            }
        }

        return $normalized;
    }
}
