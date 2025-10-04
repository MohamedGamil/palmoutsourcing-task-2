<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Facades\ProductRepository;
use Domain\Product\Entity\Product;
use Domain\Product\Service\ProductScrapingStorageServiceInterface;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\ProductUrl;
use Domain\Product\Exception\ProductNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * Create Product Use Case
 * 
 * Orchestrates the creation of a new product by scraping from a given URL
 * (Amazon or Jumia) and storing it to the database if successful.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-007: App layer implements use-cases for application logic
 * - REQ-API-003: POST endpoint for creating new watched products
 * - REQ-SCRAPE-010: Support scraping a single product by URL
 * - REQ-PERSIST-001: Store scraped product data in database
 * 
 * @package App\UseCases
 */
class CreateProductUseCase
{
    public function __construct(
        private ProductScrapingStorageServiceInterface $scrapingStorageService
    ) {}

    /**
     * Execute the use case
     * 
     * @param string $productUrl The product URL to scrape
     * @param string $platform The platform (amazon or jumia)
     * @return array Result with success status and product data or error
     * 
     * @throws \InvalidArgumentException If URL or platform is invalid
     */
    public function execute(string $productUrl, string $platform): array
    {
        Log::info('[CREATE-PRODUCT-USE-CASE] Starting product creation', [
            'url' => $productUrl,
            'platform' => $platform,
        ]);

        try {
            // Validate and create value objects
            $url = ProductUrl::fromString($productUrl);
            $platformVO = Platform::fromString($platform);

            // Check if product already exists
            if (ProductRepository::existsByUrl($url, $platformVO)) {
                Log::warning('[CREATE-PRODUCT-USE-CASE] Product already exists', [
                    'url' => $productUrl,
                    'platform' => $platform,
                ]);

                return [
                    'success' => false,
                    'error' => 'Product already exists for this URL and platform',
                    'error_code' => 'PRODUCT_ALREADY_EXISTS',
                ];
            }

            // Scrape and store the product
            $product = $this->scrapingStorageService->scrapeAndStore($url, $platformVO);

            Log::info('[CREATE-PRODUCT-USE-CASE] Product created successfully', [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'price' => $product->getPrice()->toFloat(),
            ]);

            return [
                'success' => true,
                'data' => $this->formatProduct($product),
                'message' => 'Product created and scraped successfully',
            ];

        } catch (\InvalidArgumentException $e) {
            Log::warning('[CREATE-PRODUCT-USE-CASE] Invalid input', [
                'url' => $productUrl,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'INVALID_INPUT',
            ];

        } catch (\Exception $e) {
            Log::error('[CREATE-PRODUCT-USE-CASE] Failed to create product', [
                'url' => $productUrl,
                'platform' => $platform,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to scrape or store product: ' . $e->getMessage(),
                'error_code' => 'CREATION_FAILED',
            ];
        }
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
