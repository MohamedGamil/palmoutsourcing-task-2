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

    public static function allAttemptsFailed(string $url, int $attempts, string $lastError): self
    {
        return new self("All {$attempts} scraping attempts failed for URL '{$url}'. Last error: {$lastError}");
    }

    public static function httpError(string $url, int $statusCode, string $response): self
    {
        $truncatedResponse = strlen($response) > 200 ? substr($response, 0, 200) . '...' : $response;
        return new self("HTTP {$statusCode} error for URL '{$url}'. Response: {$truncatedResponse}");
    }

    public static function emptyResponse(string $url): self
    {
        return new self("Empty response received from URL: {$url}");
    }

    public static function blocked(string $url, string $reason): self
    {
        return new self("Scraping blocked for URL '{$url}': {$reason}");
    }

    public static function dataExtractionFailed(string $url, string $reason): self
    {
        return new self("Failed to extract data from URL '{$url}': {$reason}");
    }
}
