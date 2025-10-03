<?php

declare(strict_types=1);

namespace Domain\Product\Entity;

use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\Price;
use Domain\Product\ValueObject\ProductUrl;
use Domain\Product\Exception\InvalidProductStateException;
use DateTimeInterface;
use DateTime;

/**
 * Product Domain Entity
 * 
 * Represents a product being watched from e-commerce platforms.
 * This is a framework-independent domain entity containing only business logic.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-002: Domain layer contains abstract definitions of business logic
 * - REQ-ARCH-003: Domain layer includes entities and value types
 * - REQ-MODEL-001 to REQ-MODEL-009: Product model requirements
 */
class Product
{
    private ?int $id;
    private string $title;
    private Price $price;
    private string $priceCurrency;
    private ?float $rating;
    private int $ratingCount;
    private ?string $platformCategory;
    private ?string $imageUrl;
    private ProductUrl $productUrl;
    private Platform $platform;
    private ?DateTimeInterface $lastScrapedAt;
    private int $scrapeCount;
    private bool $isActive;
    private DateTimeInterface $createdAt;
    private DateTimeInterface $updatedAt;

    /**
     * Maximum title length as per REQ-VAL-003
     */
    private const MAX_TITLE_LENGTH = 500;

    /**
     * Maximum scrape count before requiring review
     */
    private const MAX_SCRAPE_COUNT = 1000;

    /**
     * Maximum and minimum rating values
     */
    private const MIN_RATING = 0.0;
    private const MAX_RATING = 5.0;

    public function __construct(
        string $title,
        Price $price,
        ProductUrl $productUrl,
        Platform $platform,
        string $priceCurrency = 'USD',
        ?float $rating = null,
        int $ratingCount = 0,
        ?string $platformCategory = null,
        ?string $imageUrl = null,
        ?int $id = null,
        ?DateTimeInterface $lastScrapedAt = null,
        int $scrapeCount = 0,
        bool $isActive = true,
        ?DateTimeInterface $createdAt = null,
        ?DateTimeInterface $updatedAt = null
    ) {
        $this->validateTitle($title);
        $this->validateImageUrl($imageUrl);
        $this->validateProductUrlMatchesPlatform($productUrl, $platform);
        $this->validateRating($rating);
        $this->validateRatingCount($ratingCount);
        $this->validatePriceCurrency($priceCurrency);

        $this->id = $id;
        $this->title = $title;
        $this->price = $price;
        $this->priceCurrency = $priceCurrency;
        $this->rating = $rating;
        $this->ratingCount = $ratingCount;
        $this->platformCategory = $platformCategory;
        $this->imageUrl = $imageUrl;
        $this->productUrl = $productUrl;
        $this->platform = $platform;
        $this->lastScrapedAt = $lastScrapedAt;
        $this->scrapeCount = $scrapeCount;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
    }

    /**
     * Create a new product for watching
     * Factory method for creating new products
     */
    public static function createNew(
        string $title,
        Price $price,
        ProductUrl $productUrl,
        Platform $platform,
        string $priceCurrency = 'USD',
        ?float $rating = null,
        int $ratingCount = 0,
        ?string $platformCategory = null,
        ?string $imageUrl = null
    ): self {
        return new self(
            title: $title,
            price: $price,
            productUrl: $productUrl,
            platform: $platform,
            priceCurrency: $priceCurrency,
            rating: $rating,
            ratingCount: $ratingCount,
            platformCategory: $platformCategory,
            imageUrl: $imageUrl,
            isActive: true,
            scrapeCount: 0
        );
    }

    /**
     * Reconstitute product from persistence
     * Used by repository to rebuild domain entity from database
     */
    public static function reconstitute(
        int $id,
        string $title,
        Price $price,
        ProductUrl $productUrl,
        Platform $platform,
        string $priceCurrency,
        ?float $rating,
        int $ratingCount,
        ?string $platformCategory,
        ?string $imageUrl,
        ?DateTimeInterface $lastScrapedAt,
        int $scrapeCount,
        bool $isActive,
        DateTimeInterface $createdAt,
        DateTimeInterface $updatedAt
    ): self {
        return new self(
            title: $title,
            price: $price,
            productUrl: $productUrl,
            platform: $platform,
            priceCurrency: $priceCurrency,
            rating: $rating,
            ratingCount: $ratingCount,
            platformCategory: $platformCategory,
            imageUrl: $imageUrl,
            id: $id,
            lastScrapedAt: $lastScrapedAt,
            scrapeCount: $scrapeCount,
            isActive: $isActive,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    /**
     * Update product information from scraping
     */
    public function updateFromScraping(
        string $title,
        Price $price,
        ?float $rating = null,
        int $ratingCount = 0,
        ?string $platformCategory = null,
        ?string $imageUrl = null
    ): void {
        $this->validateTitle($title);
        $this->validateImageUrl($imageUrl);
        $this->validateRating($rating);
        $this->validateRatingCount($ratingCount);

        $this->title = $title;
        $this->price = $price;
        $this->rating = $rating;
        $this->ratingCount = $ratingCount;
        $this->platformCategory = $platformCategory;
        $this->imageUrl = $imageUrl;
        $this->touch();
    }

    /**
     * Mark product as scraped
     * Increments scrape count and updates last scraped timestamp
     */
    public function markAsScraped(): void {
        if (!$this->isActive) {
            throw InvalidProductStateException::cannotScrapeInactiveProduct($this->id ?? 0);
        }

        $this->scrapeCount++;
        $this->lastScrapedAt = new DateTime();
        $this->touch();

        // Check if scrape count is getting too high
        if ($this->scrapeCount > self::MAX_SCRAPE_COUNT) {
            // This is a business rule - might want to notify admin
            // For now, we'll just continue
        }
    }

    /**
     * Activate product watching
     */
    public function activate(): void {
        if ($this->isActive) {
            return; // Already active, no-op
        }

        $this->isActive = true;
        $this->touch();
    }

    /**
     * Deactivate product watching
     */
    public function deactivate(): void {
        if (!$this->isActive) {
            return; // Already inactive, no-op
        }

        $this->isActive = false;
        $this->touch();
    }

    /**
     * Check if product needs scraping
     * Business rule: Product needs scraping if never scraped or last scraped more than X hours ago
     */
    public function needsScraping(int $maxHoursSinceLastScrape = 24): bool {
        if (!$this->isActive) {
            return false;
        }

        if ($this->lastScrapedAt === null) {
            return true; // Never scraped
        }

        $hoursSinceLastScrape = (new DateTime())->diff($this->lastScrapedAt)->h;
        return $hoursSinceLastScrape >= $maxHoursSinceLastScrape;
    }

    /**
     * Update the updated_at timestamp
     */
    private function touch(): void {
        $this->updatedAt = new DateTime();
    }

    /**
     * Validate title according to business rules
     * REQ-VAL-003: Title max 500 characters
     */
    private function validateTitle(string $title): void {
        if (empty(trim($title))) {
            throw InvalidProductStateException::emptyTitle();
        }

        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            throw InvalidProductStateException::titleTooLong($title, self::MAX_TITLE_LENGTH);
        }
    }

    /**
     * Validate image URL format
     * REQ-VAL-005: Image URL must be valid URL when provided
     */
    private function validateImageUrl(?string $imageUrl): void {
        if ($imageUrl === null || empty(trim($imageUrl))) {
            return; // Nullable, so empty is valid
        }

        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            throw InvalidProductStateException::invalidImageUrl($imageUrl);
        }
    }

    /**
     * Validate that product URL matches the platform
     * REQ-VAL-002: Product URL must match platform domain
     */
    private function validateProductUrlMatchesPlatform(ProductUrl $productUrl, Platform $platform): void {
        if (!$productUrl->matchesPlatform($platform)) {
            throw InvalidProductStateException::urlPlatformMismatch(
                $productUrl->toString(),
                $platform->toString()
            );
        }
    }

    /**
     * Validate rating value
     * REQ-VAL-010: Rating must be between 0.00 and 5.00
     */
    private function validateRating(?float $rating): void
    {
        if ($rating !== null) {
            if ($rating < self::MIN_RATING || $rating > self::MAX_RATING) {
                throw InvalidProductStateException::invalidRating($rating, self::MIN_RATING, self::MAX_RATING);
            }
        }
    }

    /**
     * Validate rating count
     * REQ-VAL-011: Rating count must be non-negative
     */
    private function validateRatingCount(int $ratingCount): void
    {
        if ($ratingCount < 0) {
            throw InvalidProductStateException::invalidRatingCount($ratingCount);
        }
    }

    /**
     * Validate price currency
     * REQ-VAL-012: Must be valid ISO 4217 currency code
     */
    private function validatePriceCurrency(string $priceCurrency): void
    {
        if (!preg_match('/^[A-Z]{3}$/', $priceCurrency)) {
            throw InvalidProductStateException::invalidPriceCurrency($priceCurrency);
        }
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
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

    public function getProductUrl(): ProductUrl
    {
        return $this->productUrl;
    }

    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    public function getLastScrapedAt(): ?DateTimeInterface
    {
        return $this->lastScrapedAt;
    }

    public function getScrapeCount(): int
    {
        return $this->scrapeCount;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Check if this is a new product (not yet persisted)
     */
    public function isNew(): bool
    {
        return $this->id === null;
    }

    /**
     * Convert to array for serialization
     * Useful for logging and debugging
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => $this->price->toFloat(),
            'price_currency' => $this->priceCurrency,
            'rating' => $this->rating,
            'rating_count' => $this->ratingCount,
            'platform_category' => $this->platformCategory,
            'image_url' => $this->imageUrl,
            'product_url' => $this->productUrl->toString(),
            'platform' => $this->platform->toString(),
            'last_scraped_at' => $this->lastScrapedAt?->format('Y-m-d H:i:s'),
            'scrape_count' => $this->scrapeCount,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
