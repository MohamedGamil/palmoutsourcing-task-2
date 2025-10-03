<?php

declare(strict_types=1);

namespace Domain\Product\Exception;

/**
 * Unsupported Platform Exception
 * 
 * Thrown when attempting operations on unsupported platforms.
 */
final class UnsupportedPlatformException extends DomainException
{
    public static function forScraping(string $platform): self
    {
        return new self("Scraping is not supported for platform: {$platform}");
    }

    public static function noScraperAvailable(string $platform): self
    {
        return new self("No scraper implementation available for platform: {$platform}");
    }
}
