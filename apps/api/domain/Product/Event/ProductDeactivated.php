<?php

declare(strict_types=1);

namespace Domain\Product\Event;

use Domain\Product\Entity\Product;

/**
 * Product Deactivated Event
 * 
 * Fired when a product is deactivated from watching.
 */
final class ProductDeactivated extends DomainEvent
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
        return 'product.deactivated';
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
