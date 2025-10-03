<?php

declare(strict_types=1);

namespace Domain\Product\Service;

use Domain\Product\ValueObject\Price;

/**
 * Scraped Product Data DTO
 * 
 * Data Transfer Object representing scraped product information.
 * Immutable value object carrying scraped data.
 */
final class ScrapedProductData
{
    private string $title;
    private Price $price;
    private string $priceCurrency;
    private ?float $rating;
    private int $ratingCount;
    private ?string $platformCategory;
    private ?string $imageUrl;
    private ?string $platformId;

    public function __construct(
        string $title,
        Price $price,
        string $priceCurrency = 'USD',
        ?float $rating = null,
        int $ratingCount = 0,
        ?string $platformCategory = null,
        ?string $imageUrl = null,
        ?string $platformId = null
    ) {
        $this->title = $title;
        $this->price = $price;
        $this->priceCurrency = $priceCurrency;
        $this->rating = $rating;
        $this->ratingCount = $ratingCount;
        $this->platformCategory = $platformCategory;
        $this->imageUrl = $imageUrl;
        $this->platformId = $platformId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getPriceCurrency(): string
    {
        return $this->priceCurrency;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function getRatingCount(): int
    {
        return $this->ratingCount;
    }

    public function getPlatformCategory(): ?string
    {
        return $this->platformCategory;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getPlatformId(): ?string
    {
        return $this->platformId;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'price' => $this->price->toFloat(),
            'price_currency' => $this->priceCurrency,
            'rating' => $this->rating,
            'rating_count' => $this->ratingCount,
            'platform_category' => $this->platformCategory,
            'image_url' => $this->imageUrl,
            'platform_id' => $this->platformId,
        ];
    }
}
