<?php

declare(strict_types=1);

namespace Domain\Product\Exception;

/**
 * Scraping Exception
 * 
 * Thrown when scraping operations fail.
 */
final class ScrapingException extends DomainException
{
    public static function failed(string $url, string $reason): self
    {
        return new self("Failed to scrape product from '{$url}': {$reason}");
    }

    public static function timeout(string $url, int $timeoutSeconds): self
    {
        return new self("Scraping timed out after {$timeoutSeconds} seconds for URL: {$url}");
    }

    public static function invalidResponse(string $url, int $statusCode): self
    {
        return new self("Invalid response (HTTP {$statusCode}) from URL: {$url}");
    }

    public static function elementNotFound(string $url, string $element): self
    {
        return new self("Required element '{$element}' not found when scraping: {$url}");
    }

    public static function proxyUnavailable(): self
    {
        return new self("No healthy proxies available for scraping.");
    }
}
