<?php

declare(strict_types=1);

namespace Domain\Product\Exception;

/**
 * Invalid Product State Exception
 * 
 * Thrown when a product operation violates business rules.
 */
final class InvalidProductStateException extends DomainException
{
    public static function emptyTitle(): self
    {
        return new self("Product title cannot be empty.");
    }

    public static function titleTooLong(string $title, int $maxLength): self
    {
        $actualLength = mb_strlen($title);
        return new self(
            "Product title exceeds maximum length of {$maxLength} characters (actual: {$actualLength})."
        );
    }

    public static function invalidImageUrl(string $url): self
    {
        return new self("Image URL '{$url}' is not a valid URL format.");
    }

    public static function urlPlatformMismatch(string $url, string $platform): self
    {
        return new self(
            "Product URL '{$url}' does not match the specified platform '{$platform}'."
        );
    }

    public static function cannotScrapeInactiveProduct(int $productId): self
    {
        return new self("Cannot scrape inactive product (ID: {$productId}).");
    }

    public static function invalidRating(float $rating, float $min, float $max): self
    {
        return new self("Rating {$rating} is invalid. Must be between {$min} and {$max}.");
    }

    public static function invalidRatingCount(int $count): self
    {
        return new self("Rating count {$count} is invalid. Must be non-negative.");
    }

    public static function invalidPriceCurrency(string $currency): self
    {
        return new self("Price currency '{$currency}' is invalid. Must be a valid ISO 4217 code (3 uppercase letters).");
    }
}
