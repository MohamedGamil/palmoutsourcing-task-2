<?php

declare(strict_types=1);

namespace Domain\Product\Service;

use Domain\Product\ValueObject\ProductUrl;
use Domain\Product\ValueObject\Platform;

/**
 * Scraping Service Interface
 * 
 * Defines the contract for web scraping operations.
 * This interface is framework-independent and will be implemented in the application layer.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-006: App layer implements services based on domain layer contracts
 * - REQ-SCRAPE-001 to REQ-SCRAPE-004: Scraping service requirements
 */
interface ScrapingServiceInterface
{
    /**
     * Scrape product data from a URL
     * 
     * @return ScrapedProductData
     * @throws ScrapingException
     */
    public function scrapeProduct(ProductUrl $url, Platform $platform): ScrapedProductData;

    /**
     * Check if scraping is supported for a platform
     */
    public function supportsPlatform(Platform $platform): bool;

    /**
     * Get the scraper implementation for a platform
     * 
     * @throws UnsupportedPlatformException
     */
    public function getScraperForPlatform(Platform $platform): PlatformScraperInterface;

    /**
     * Test scraping functionality for a platform
     * 
     * @param Platform $platform
     * @return array Array of test results (success or error details)
     */
    public function testPlatformScraping(Platform $platform): array;


    /**
     * Get service health information
     * 
     * @return array
     */
    public function getHealthStatus(): array;


    /**
     * Get scraping statistics
     * 
     * @return array
     */
    public function getStatistics(): array;

    /**
     * Get list of supported platforms
     * 
     * @return array
     */
    public function getSupportedPlatforms(): array;
}
