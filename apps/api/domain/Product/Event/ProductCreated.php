<?php

declare(strict_types=1);

namespace Domain\Product\Event;

use Domain\Product\Entity\Product;

/**
 * Product Created Event
 * 
 * Fired when a new product is created.
 */
final class ProductCreated extends DomainEvent
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
        return 'product.created';
    }

    public function toArray(): array
    {
        return [
            'event' => $this->getEventName(),
            'occurred_at' => $this->occurredAt()->format('Y-m-d H:i:s'),
            'product' => $this->product->toArray(),
        ];
    }
}
