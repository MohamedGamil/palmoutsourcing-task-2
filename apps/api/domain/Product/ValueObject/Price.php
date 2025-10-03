<?php

declare(strict_types=1);

namespace Domain\Product\ValueObject;

use Domain\Product\Exception\InvalidPriceException;

/**
 * Price Value Object
 * 
 * Represents a monetary price with validation.
 * Immutable value object ensuring price validity.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-003: Domain layer includes value types
 * - REQ-VAL-001: Price must be numeric and >= 0
 */
final class Price
{
    private const MIN_PRICE = 0.0;
    private const MAX_PRICE = 999999999.99; // ~1 billion
    private const PRECISION = 2; // Two decimal places

    private float $amount;

    private function __construct(float $amount)
    {
        $this->validate($amount);
        $this->amount = round($amount, self::PRECISION);
    }

    public static function fromFloat(float $amount): self
    {
        return new self($amount);
    }

    public static function fromString(string $amount): self
    {
        $cleaned = preg_replace('/[^0-9.]/', '', $amount);
        
        if (!is_numeric($cleaned)) {
            throw InvalidPriceException::notNumeric($amount);
        }

        return new self((float) $cleaned);
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    private function validate(float $amount): void
    {
        if ($amount < self::MIN_PRICE) {
            throw InvalidPriceException::tooLow($amount, self::MIN_PRICE);
        }

        if ($amount > self::MAX_PRICE) {
            throw InvalidPriceException::tooHigh($amount, self::MAX_PRICE);
        }

        if (!is_finite($amount)) {
            throw InvalidPriceException::notFinite($amount);
        }
    }

    public function toFloat(): float
    {
        return $this->amount;
    }

    public function toString(): string
    {
        return number_format($this->amount, self::PRECISION, '.', '');
    }

    /**
     * Format price with currency symbol
     */
    public function toFormattedString(string $currency = 'EGP'): string
    {
        return number_format($this->amount, self::PRECISION, '.', ',') . ' ' . $currency;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function equals(Price $other): bool
    {
        return abs($this->amount - $other->amount) < 0.001; // Float comparison with epsilon
    }

    /**
     * Check if this price is greater than another
     */
    public function isGreaterThan(Price $other): bool
    {
        return $this->amount > $other->amount;
    }

    /**
     * Check if this price is less than another
     */
    public function isLessThan(Price $other): bool
    {
        return $this->amount < $other->amount;
    }

    /**
     * Check if price is zero
     */
    public function isZero(): bool
    {
        return $this->amount < 0.001;
    }

    /**
     * Add another price
     */
    public function add(Price $other): self
    {
        return new self($this->amount + $other->amount);
    }

    /**
     * Subtract another price
     */
    public function subtract(Price $other): self
    {
        return new self($this->amount - $other->amount);
    }

    /**
     * Multiply by a factor
     */
    public function multiply(float $factor): self
    {
        return new self($this->amount * $factor);
    }

    /**
     * Calculate percentage difference from another price
     */
    public function percentageDifferenceFrom(Price $other): float
    {
        if ($other->isZero()) {
            return 0.0;
        }

        return (($this->amount - $other->amount) / $other->amount) * 100;
    }
}
