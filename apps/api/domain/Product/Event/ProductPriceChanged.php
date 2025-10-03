<?php

declare(strict_types=1);

namespace Domain\Product\Event;

use Domain\Product\Entity\Product;
use Domain\Product\ValueObject\Price;

/**
 * Product Price Changed Event
 * 
 * Fired when a product's price changes during scraping.
 */
final class ProductPriceChanged extends DomainEvent
{
    private Product $product;
    private Price $oldPrice;
    private Price $newPrice;

    public function __construct(Product $product, Price $oldPrice, Price $newPrice)
    {
        parent::__construct();
        $this->product = $product;
        $this->oldPrice = $oldPrice;
        $this->newPrice = $newPrice;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getOldPrice(): Price
    {
        return $this->oldPrice;
    }

    public function getNewPrice(): Price
    {
        return $this->newPrice;
    }

    public function getPriceChange(): Price
    {
        return $this->newPrice->subtract($this->oldPrice);
    }

    public function getPercentageChange(): float
    {
        return $this->newPrice->percentageDifferenceFrom($this->oldPrice);
    }

    public function isPriceIncrease(): bool
    {
        return $this->newPrice->isGreaterThan($this->oldPrice);
    }

    public function isPriceDecrease(): bool
    {
        return $this->newPrice->isLessThan($this->oldPrice);
    }

    public function getEventName(): string
    {
        return 'product.price_changed';
    }

    public function toArray(): array
    {
        return [
            'event' => $this->getEventName(),
            'occurred_at' => $this->occurredAt()->format('Y-m-d H:i:s'),
            'product_id' => $this->product->getId(),
            'old_price' => $this->oldPrice->toFloat(),
            'new_price' => $this->newPrice->toFloat(),
            'price_change' => $this->getPriceChange()->toFloat(),
            'percentage_change' => $this->getPercentageChange(),
        ];
    }
}
