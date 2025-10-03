<?php

declare(strict_types=1);

namespace Domain\Product\Exception;

/**
 * Product Not Found Exception
 * 
 * Thrown when a product cannot be found in the repository.
 */
final class ProductNotFoundException extends DomainException
{
    public static function byId(int $id): self
    {
        return new self("Product with ID {$id} not found.");
    }

    public static function byUrl(string $url): self
    {
        return new self("Product with URL '{$url}' not found.");
    }

    public static function byUrlAndPlatform(string $url, string $platform): self
    {
        return new self("Product with URL '{$url}' on platform '{$platform}' not found.");
    }
}
