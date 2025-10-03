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
}
