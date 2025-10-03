<?php

declare(strict_types=1);

namespace Domain\Product\Event;

use Domain\Product\Entity\Product;

/**
 * Product Scraped Event
 * 
 * Fired when a product is successfully scraped.
 */
final class ProductScraped extends DomainEvent
{
    private Product $product;
    private bool $dataChanged;

    public function __construct(Product $product, bool $dataChanged = false)
    {
        parent::__construct();
        $this->product = $product;
        $this->dataChanged = $dataChanged;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function hasDataChanged(): bool
    {
        return $this->dataChanged;
    }

    public function getEventName(): string
    {
        return 'product.scraped';
    }

    public function toArray(): array
    {
        return [
            'event' => $this->getEventName(),
            'occurred_at' => $this->occurredAt()->format('Y-m-d H:i:s'),
            'product_id' => $this->product->getId(),
            'data_changed' => $this->dataChanged,
        ];
    }
}
