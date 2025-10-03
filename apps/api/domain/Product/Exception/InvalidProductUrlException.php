<?php

declare(strict_types=1);

namespace Domain\Product\Exception;

/**
 * Invalid Product URL Exception
 * 
 * Thrown when an invalid product URL is provided.
 */
final class InvalidProductUrlException extends DomainException
{
    public static function empty(): self
    {
        return new self("Product URL cannot be empty.");
    }

    public static function tooLong(string $url, int $maxLength): self
    {
        $actualLength = mb_strlen($url);
        return new self(
            "Product URL exceeds maximum length of {$maxLength} characters (actual: {$actualLength})."
        );
    }

    public static function invalidFormat(string $url): self
    {
        return new self("Product URL '{$url}' is not a valid URL format.");
    }

    public static function invalidScheme(string $url, ?string $scheme): self
    {
        return new self(
            "Product URL must use HTTP or HTTPS scheme. Got: " . ($scheme ?? 'none')
        );
    }

    public static function missingHost(string $url): self
    {
        return new self("Product URL '{$url}' is missing a hostname.");
    }

    public static function unsupportedPlatform(string $url): self
    {
        return new self("Unable to detect supported platform from URL: '{$url}'");
    }
}
