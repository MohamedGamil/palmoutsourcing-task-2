<?php

declare(strict_types=1);

namespace App\Services;

use App\Facades\ProductRepository;
use App\Facades\ScrapingOrchestrator;
use Domain\Product\Entity\Product;
use Domain\Product\Service\ProductScrapingStorageServiceInterface;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\Price;
use Domain\Product\ValueObject\ProductUrl;
use Illuminate\Support\Facades\Log;

/**
 * Product Scraping and Storage Service
 * 
 * This service combines scraping orchestration with data persistence.
 * It demonstrates how to use the ScrapingOrchestrator with ProductRepository
 * to scrape products and store the results in the database.
 * 
 * Requirements Implemented:
 * - REQ-PERSIST-001: Store scraped product data in database
 * - REQ-PERSIST-002: Update existing products with new scraping data
 * - REQ-SCRAPE-020: Integrate scraping with data persistence
 */
class ProductScrapingStorageService implements ProductScrapingStorageServiceInterface
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
    public function scrapeAndStore(ProductUrl $url, Platform $platform): Product
    {
        Log::info('[PRODUCT-SCRAPING-STORAGE] Starting scrape and store', [
            'url' => $url->toString(),
            'platform' => $platform->toString(),
        ]);

        try {
            // Scrape the product using the orchestrator
            $orchestratorResult = ScrapingOrchestrator::scrapeAndMapProduct($url->toString());
            
            if ($orchestratorResult['status'] !== 'success') {
                throw new \Exception('Scraping failed: ' . ($orchestratorResult['error'] ?? 'Unknown error'));
            }
            
            $mappedData = $orchestratorResult['mapped_data'];
            
            // Check if product already exists
            $existingProduct = ProductRepository::findByUrlOrNull($url, $platform);
            
            if ($existingProduct) {
                // Update existing product
                $product = $this->updateExistingProduct($existingProduct, $mappedData);
                Log::info('[PRODUCT-SCRAPING-STORAGE] Updated existing product', [
                    'id' => $product->getId(),
                    'title' => $product->getTitle(),
                    'old_price' => $existingProduct->getPrice()->toFloat(),
                    'new_price' => $product->getPrice()->toFloat(),
                ]);
            } else {
                // Create new product
                $product = $this->createNewProduct($mappedData, $url, $platform);
                Log::info('[PRODUCT-SCRAPING-STORAGE] Created new product', [
                    'id' => $product->getId(),
                    'title' => $product->getTitle(),
                    'price' => $product->getPrice()->toFloat(),
                ]);
            }

            return $product;

        } catch (\Exception $e) {
            Log::error('[PRODUCT-SCRAPING-STORAGE] Failed to scrape and store product', [
                'url' => $url->toString(),
                'platform' => $platform->toString(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Scrape and store multiple products
     * 
     * @param array $urlPlatformPairs Array of ['url' => ProductUrl, 'platform' => Platform]
     * @return array Array of ['success' => Product[], 'failed' => array with error details]
     */
    public function scrapeAndStoreMultiple(array $urlPlatformPairs): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($urlPlatformPairs as $pair) {
            $url = $pair['url'];
            $platform = $pair['platform'];
            
            try {
                $product = $this->scrapeAndStore($url, $platform);
                $results['success'][] = $product;
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'url' => $url->toString(),
                    'platform' => $platform->toString(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('[PRODUCT-SCRAPING-STORAGE] Batch scraping completed', [
            'total' => count($urlPlatformPairs),
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed']),
        ]);

        return $results;
    }

    /**
     * Re-scrape all products that need updating
     * 
     * @param int $maxHoursSinceLastScrape Hours since last scrape to consider "needs scraping"
     * @return array Array of scraping results
     */
    public function updateProductsNeedingScraping(int $maxHoursSinceLastScrape = 24): array
    {
        $productsNeedingScraping = ProductRepository::findProductsNeedingScraping($maxHoursSinceLastScrape);
        
        Log::info('[PRODUCT-SCRAPING-STORAGE] Found products needing re-scraping', [
            'count' => count($productsNeedingScraping),
            'max_hours_since_last_scrape' => $maxHoursSinceLastScrape,
        ]);

        $urlPlatformPairs = [];
        foreach ($productsNeedingScraping as $product) {
            $urlPlatformPairs[] = [
                'url' => $product->getProductUrl(),
                'platform' => $product->getPlatform(),
            ];
        }

        return $this->scrapeAndStoreMultiple($urlPlatformPairs);
    }

    /**
     * Get statistics about stored products
     * 
     * @return array Statistics about products in the database
     */
    public function getStorageStatistics(): array
    {
        return [
            'total_products' => ProductRepository::count(),
            'active_products' => ProductRepository::countActive(),
            'amazon_products' => ProductRepository::countByPlatform(Platform::fromString('amazon')),
            'jumia_products' => ProductRepository::countByPlatform(Platform::fromString('jumia')),
            'products_needing_scraping_24h' => count(ProductRepository::findProductsNeedingScraping(24)),
            'products_needing_scraping_1h' => count(ProductRepository::findProductsNeedingScraping(1)),
        ];
    }

    /**
     * Update existing product with scraped data
     */
    private function updateExistingProduct(Product $existingProduct, array $mappedData): Product
    {
        // Update product with new scraped data
        $existingProduct->updateFromScraping(
            title: $mappedData['title'],
            price: Price::fromFloat($mappedData['price']),
            rating: $mappedData['rating'] ?? null,
            ratingCount: $mappedData['rating_count'] ?? 0,
            platformCategory: $mappedData['category'] ?? null,
            imageUrl: $mappedData['image_url'] ?? null
        );

        // Mark as scraped to update scrape count and timestamp
        $existingProduct->markAsScraped();

        // Save to database
        return ProductRepository::save($existingProduct);
    }

    /**
     * Create new product from scraped data
     */
    private function createNewProduct(array $mappedData, ProductUrl $url, Platform $platform): Product
    {
        // Create new domain product
        $product = Product::createNew(
            title: $mappedData['title'],
            price: Price::fromFloat($mappedData['price']),
            productUrl: $url,
            platform: $platform,
            priceCurrency: $mappedData['price_currency'] ?? 'USD',
            rating: $mappedData['rating'] ?? null,
            ratingCount: $mappedData['rating_count'] ?? 0,
            platformCategory: $mappedData['category'] ?? null,
            imageUrl: $mappedData['image_url'] ?? null
        );

        // Mark as scraped for the first time
        $product->markAsScraped();

        // Save to database
        return ProductRepository::save($product);
    }
}