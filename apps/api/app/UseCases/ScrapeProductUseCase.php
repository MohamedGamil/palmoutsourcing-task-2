<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Facades\ProductRepository;
use Domain\Product\Entity\Product;
use Domain\Product\Service\ProductScrapingStorageServiceInterface;
use Domain\Product\Exception\ProductNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * Scrape Product Use Case
 * 
 * Manually triggers the scraping process for an existing product,
 * updating it with fresh data from the e-commerce platform.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-007: App layer implements use-cases for application logic
 * - REQ-API-007: Manual scrape trigger endpoint
 * - REQ-SCRAPE-010: Support scraping a single product by URL
 * - REQ-PERSIST-002: Update existing products with new scraping data
 * 
 * @package App\UseCases
 */
class ScrapeProductUseCase
{
    public function __construct(
        private ProductScrapingStorageServiceInterface $scrapingStorageService
    ) {}

    /**
     * Execute manual scrape for a single product by ID
     * 
     * @param int $productId The product ID to scrape
     * @return array Result with success status and product data or error
     */
    public function execute(int $productId): array
    {
        Log::info('[SCRAPE-PRODUCT-USE-CASE] Starting manual scrape', [
            'product_id' => $productId,
        ]);

        try {
            // Find the product
            $product = ProductRepository::findById($productId);

            // Check if product is active
            if (!$product->isActive()) {
                Log::warning('[SCRAPE-PRODUCT-USE-CASE] Attempting to scrape inactive product', [
                    'product_id' => $productId,
                ]);

                return [
                    'success' => false,
                    'error' => 'Cannot scrape inactive product. Please activate it first.',
                    'error_code' => 'PRODUCT_INACTIVE',
                ];
            }

            return $this->scrapeProduct($product);

        } catch (ProductNotFoundException $e) {
            Log::warning('[SCRAPE-PRODUCT-USE-CASE] Product not found', [
                'product_id' => $productId,
            ]);

            return [
                'success' => false,
                'error' => 'Product not found',
                'error_code' => 'PRODUCT_NOT_FOUND',
            ];

        } catch (\Exception $e) {
            Log::error('[SCRAPE-PRODUCT-USE-CASE] Failed to scrape product', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to scrape product: ' . $e->getMessage(),
                'error_code' => 'SCRAPE_FAILED',
            ];
        }
    }

    /**
     * Execute manual scrape for multiple products
     * 
     * @param array $productIds Array of product IDs
     * @return array Result with successful and failed scrapes
     */
    public function batchScrape(array $productIds): array
    {
        Log::info('[SCRAPE-PRODUCT-USE-CASE] Starting batch scrape', [
            'product_count' => count($productIds),
        ]);

        $results = [
            'successful' => [],
            'failed' => [],
        ];

        $startTime = microtime(true);

        foreach ($productIds as $productId) {
            $result = $this->execute($productId);

            if ($result['success']) {
                $results['successful'][] = [
                    'product_id' => $productId,
                    'product' => $result['product'],
                    'changes' => $result['changes'] ?? [],
                ];
            } else {
                $results['failed'][] = [
                    'product_id' => $productId,
                    'error' => $result['error'],
                    'error_code' => $result['error_code'],
                ];
            }
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $successCount = count($results['successful']);
        $failedCount = count($results['failed']);

        Log::info('[SCRAPE-PRODUCT-USE-CASE] Batch scrape completed', [
            'total' => count($productIds),
            'successful' => $successCount,
            'failed' => $failedCount,
            'duration_seconds' => $duration,
        ]);

        return [
            'success' => true,
            'summary' => [
                'total' => count($productIds),
                'successful' => $successCount,
                'failed' => $failedCount,
                'success_rate' => count($productIds) > 0 
                    ? round(($successCount / count($productIds)) * 100, 2) 
                    : 0,
                'duration_seconds' => $duration,
            ],
            'results' => $results,
            'message' => sprintf(
                'Batch scraping completed: %d successful, %d failed out of %d total',
                $successCount,
                $failedCount,
                count($productIds)
            ),
        ];
    }

    /**
     * Scrape all active products that need updating
     * 
     * @param int $maxHoursSinceLastScrape Hours since last scrape to consider "needs scraping"
     * @return array Result with scraping statistics
     */
    public function scrapeProductsNeedingUpdate(int $maxHoursSinceLastScrape = 24): array
    {
        Log::info('[SCRAPE-PRODUCT-USE-CASE] Starting scrape for products needing update', [
            'max_hours_since_last_scrape' => $maxHoursSinceLastScrape,
        ]);

        try {
            $result = $this->scrapingStorageService->updateProductsNeedingScraping(
                $maxHoursSinceLastScrape
            );

            return [
                'success' => true,
                'summary' => [
                    'total' => count($result['success']) + count($result['failed']),
                    'successful' => count($result['success']),
                    'failed' => count($result['failed']),
                ],
                'results' => [
                    'successful' => array_map(
                        fn($product) => [
                            'product_id' => $product->getId(),
                            'title' => $product->getTitle(),
                            'price' => $product->getPrice()->toFloat(),
                        ],
                        $result['success']
                    ),
                    'failed' => $result['failed'],
                ],
                'message' => sprintf(
                    'Updated %d products successfully, %d failed',
                    count($result['success']),
                    count($result['failed'])
                ),
            ];

        } catch (\Exception $e) {
            Log::error('[SCRAPE-PRODUCT-USE-CASE] Failed to scrape products needing update', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to scrape products: ' . $e->getMessage(),
                'error_code' => 'BATCH_SCRAPE_FAILED',
            ];
        }
    }

    /**
     * Common scrape logic for a single product
     */
    private function scrapeProduct(Product $product): array
    {
        $oldPrice = $product->getPrice()->toFloat();
        $oldRating = $product->getRating();
        $oldScrapeCount = $product->getScrapeCount();

        // Re-scrape and update the product
        $updatedProduct = $this->scrapingStorageService->scrapeAndStore(
            $product->getProductUrl(),
            $product->getPlatform()
        );

        $priceChanged = $oldPrice !== $updatedProduct->getPrice()->toFloat();
        $ratingChanged = $oldRating !== $updatedProduct->getRating();

        Log::info('[SCRAPE-PRODUCT-USE-CASE] Product scraped successfully', [
            'id' => $updatedProduct->getId(),
            'title' => $updatedProduct->getTitle(),
            'old_price' => $oldPrice,
            'new_price' => $updatedProduct->getPrice()->toFloat(),
            'price_changed' => $priceChanged,
            'rating_changed' => $ratingChanged,
            'scrape_count' => $updatedProduct->getScrapeCount(),
        ]);

        return [
            'success' => true,
            'product' => $this->formatProduct($updatedProduct),
            'changes' => [
                'price_changed' => $priceChanged,
                'old_price' => $oldPrice,
                'new_price' => $updatedProduct->getPrice()->toFloat(),
                'price_difference' => $updatedProduct->getPrice()->toFloat() - $oldPrice,
                'rating_changed' => $ratingChanged,
                'old_rating' => $oldRating,
                'new_rating' => $updatedProduct->getRating(),
                'scrape_count_before' => $oldScrapeCount,
                'scrape_count_after' => $updatedProduct->getScrapeCount(),
            ],
            'message' => 'Product scraped and updated successfully',
        ];
    }

    /**
     * Format product entity for response
     */
    private function formatProduct(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'title' => $product->getTitle(),
            'price' => $product->getPrice()->toFloat(),
            'price_currency' => $product->getPriceCurrency(),
            'rating' => $product->getRating(),
            'rating_count' => $product->getRatingCount(),
            'image_url' => $product->getImageUrl(),
            'product_url' => $product->getProductUrl()->toString(),
            'platform' => $product->getPlatform()->toString(),
            'platform_id' => $product->getPlatformId(),
            'platform_category' => $product->getPlatformCategory(),
            'last_scraped_at' => $product->getLastScrapedAt()?->format('Y-m-d H:i:s'),
            'scrape_count' => $product->getScrapeCount(),
            'is_active' => $product->isActive(),
            'created_at' => $product->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $product->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
