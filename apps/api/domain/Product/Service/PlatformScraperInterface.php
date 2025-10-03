<?php

declare(strict_types=1);

namespace Domain\Product\Service;

use Domain\Product\ValueObject\ProductUrl;

/**
 * Platform Scraper Interface
 * 
 * Defines the contract for platform-specific scrapers.
 * Each platform (Amazon, Jumia) will have its own implementation.
 */
interface PlatformScraperInterface
{
    /**
     * Scrape product data from a URL
     * 
     * @return ScrapedProductData
     * @throws ScrapingException
     */
    public function scrape(ProductUrl $url): ScrapedProductData;

    /**
     * Get the platform name this scraper handles
     */
    public function getPlatformName(): string;
}
