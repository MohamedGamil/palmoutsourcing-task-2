<?php

declare(strict_types=1);

namespace Domain\Product\Event;

use Domain\Product\Entity\Product;

/**
 * Product Activated Event
 * 
 * Fired when a product is activated for watching.
 */
final class ProductActivated extends DomainEvent
{
    private Product $product;

    public function __construct(Product $product)
    {
        parent::__construct();
        $this->product = $product;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getEventName(): string
    {
        return 'product.activated';
    }

    public function toArray(): array
    {
        return [
            'event' => $this->getEventName(),
            'occurred_at' => $this->occurredAt()->format('Y-m-d H:i:s'),
            'product_id' => $this->product->getId(),
        ];
    }
}
