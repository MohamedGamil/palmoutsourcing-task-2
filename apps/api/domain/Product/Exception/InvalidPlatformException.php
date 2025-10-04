<?php

declare(strict_types=1);

namespace Domain\Product\Exception;

/**
 * Invalid Platform Exception
 * 
 * Thrown when an invalid platform is provided.
 */
final class InvalidPlatformException extends DomainException
{
    public static function unsupported(string $platform, array $supported): self
    {
        $supportedList = implode(', ', $supported);
        return new self(
            "Platform '{$platform}' is not supported. Supported platforms: {$supportedList}"
        );
    }

    public static function cannotDetect(string $url): self
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? 'unknown';
        
        return new self(
            "Cannot detect platform from URL '{$url}'. Domain '{$host}' does not match any supported platform (amazon, jumia)."
        );
    }
}
