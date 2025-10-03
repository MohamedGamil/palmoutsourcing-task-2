<?php

declare(strict_types=1);

namespace Domain\Product\Service;

use Domain\Product\Entity\Product;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\ProductUrl;

/**
 * Product Scraping Storage Service Interface
 * 
 * Domain contract for services that combine scraping orchestration with data persistence.
 * This interface defines the contract for scraping products and storing the results
 * in a repository, handling both new products and updates to existing ones.
 * 
 * Requirements Implemented:
 * - REQ-PERSIST-001: Store scraped product data in database
 * - REQ-PERSIST-002: Update existing products with new scraping data
 * - REQ-SCRAPE-020: Integrate scraping with data persistence
 * - REQ-ARCH-006: Domain layer SHALL define service contracts
 */
interface ProductScrapingStorageServiceInterface
{
    /**
     * Scrape a product and store it in the database
     * 
     * If the product already exists (by URL and platform), it will be updated.
     * If it doesn't exist, a new product will be created.
     * 
     * @param ProductUrl $url The URL to scrape
     * @param Platform $platform The platform (amazon/jumia)
     * @return Product The saved product
     * @throws \Exception If scraping fails or product cannot be saved
     */
    public function scrapeAndStore(ProductUrl $url, Platform $platform): Product;

    /**
     * Scrape and store multiple products
     * 
     * @param array $urlPlatformPairs Array of ['url' => ProductUrl, 'platform' => Platform]
     * @return array Array of ['success' => Product[], 'failed' => array with error details]
     */
    public function scrapeAndStoreMultiple(array $urlPlatformPairs): array;

    /**
     * Re-scrape all products that need updating
     * 
     * Finds products that haven't been scraped recently and updates them
     * with fresh data from their respective platforms.
     * 
     * @param int $maxHoursSinceLastScrape Hours since last scrape to consider "needs scraping"
     * @return array Array of scraping results with success and failure details
     */
    public function updateProductsNeedingScraping(int $maxHoursSinceLastScrape = 24): array;

    /**
     * Get statistics about stored products
     * 
     * Provides comprehensive statistics about the products stored in the system,
     * including counts by platform, activity status, and scraping freshness.
     * 
     * @return array Statistics about products in the database
     */
    public function getStorageStatistics(): array;
}