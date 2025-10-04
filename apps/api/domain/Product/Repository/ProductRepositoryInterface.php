<?php

declare(strict_types=1);

namespace Domain\Product\Repository;

use Domain\Product\Entity\Product;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\ProductUrl;
use Domain\Product\Exception\ProductNotFoundException;

/**
 * Product Repository Interface
 * 
 * Defines the contract for product persistence operations.
 * This interface is framework-independent and will be implemented in the application layer.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-005: App layer implements repositories based on domain layer contracts
 * - REQ-REPO-001 to REQ-REPO-006: Repository operations
 */
interface ProductRepositoryInterface
{
    /**
     * Find a product by its ID
     * 
     * @throws ProductNotFoundException
     */
    public function findById(int $id): Product;

    /**
     * Find a product by ID or return null if not found
     */
    public function findByIdOrNull(int $id): ?Product;

    /**
     * Find a product by URL and platform
     * 
     * @throws ProductNotFoundException
     */
    public function findByUrl(ProductUrl $url, Platform $platform): Product;

    /**
     * Find a product by URL and platform or return null
     */
    public function findByUrlOrNull(ProductUrl $url, Platform $platform): ?Product;

    /**
     * Save a product (create or update)
     * 
     * @return Product The saved product with updated ID and timestamps
     */
    public function save(Product $product): Product;

    /**
     * Delete a product
     */
    public function delete(Product $product): void;

    /**
     * Find all active products
     * 
     * @return Product[]
     */
    public function findAllActive(): array;

    /**
     * Find active products by platform
     * 
     * @return Product[]
     */
    public function findActiveByPlatform(Platform $platform): array;

    /**
     * Find products that need scraping
     * 
     * @param int $maxHoursSinceLastScrape Hours since last scrape to consider "needs scraping"
     * @return Product[]
     */
    public function findProductsNeedingScraping(int $maxHoursSinceLastScrape = 24): array;

    /**
     * Find products for scraping with intelligent prioritization
     * Priority: stale (never scraped) > least scraped > outdated
     * 
     * @param int $limit Maximum number of products to return
     * @param int $maxHoursSinceLastScrape Hours since last scrape to consider "outdated"
     * @return Product[]
     */
    public function findProductsForScraping(int $limit = 100, int $maxHoursSinceLastScrape = 24): array;

    /**
     * Count total products
     */
    public function count(): int;

    /**
     * Count active products
     */
    public function countActive(): int;

    /**
     * Count products by platform
     */
    public function countByPlatform(Platform $platform): int;

    /**
     * Check if a product exists by URL and platform
     */
    public function existsByUrl(ProductUrl $url, Platform $platform): bool;
}
