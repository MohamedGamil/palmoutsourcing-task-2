<?php

declare(strict_types=1);

namespace Domain\Product\Exception;

/**
 * Invalid Price Exception
 * 
 * Thrown when an invalid price is provided.
 */
final class InvalidPriceException extends DomainException
{
    public static function notNumeric(string $value): self
    {
        return new self("Price '{$value}' is not numeric.");
    }

    public static function tooLow(float $amount, float $min): self
    {
        return new self("Price {$amount} is below minimum allowed price of {$min}.");
    }

    public static function tooHigh(float $amount, float $max): self
    {
        return new self("Price {$amount} exceeds maximum allowed price of {$max}.");
    }

    public static function notFinite(float $amount): self
    {
        return new self("Price must be a finite number, got: {$amount}.");
    }
}
