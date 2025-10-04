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
 * Update Product Use Case
 * 
 * Checks if a product exists by ID, platform_id, or platform_url,
 * then re-scrapes and updates it with fresh data.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-007: App layer implements use-cases for application logic
 * - REQ-API-005: PUT/PATCH endpoint for updating products
 * - REQ-PERSIST-002: Update existing products with new scraping data
 * - REQ-SCRAPE-010: Support scraping a single product by URL
 * 
 * @package App\UseCases
 */
class UpdateProductUseCase
{
    public function __construct(
        private ProductScrapingStorageServiceInterface $scrapingStorageService
    ) {}

    /**
     * Execute the use case by product ID
     * 
     * @param int $productId The product ID to update
     * @return array Result with success status and product data or error
     */
    public function executeById(int $productId): array
    {
        Log::info('[UPDATE-PRODUCT-USE-CASE] Starting product update by ID', [
            'product_id' => $productId,
        ]);

        try {
            // Find the product
            $product = ProductRepository::findById($productId);

            return $this->updateProduct($product);

        } catch (ProductNotFoundException $e) {
            Log::warning('[UPDATE-PRODUCT-USE-CASE] Product not found', [
                'product_id' => $productId,
            ]);

            return [
                'success' => false,
                'error' => 'Product not found',
                'error_code' => 'PRODUCT_NOT_FOUND',
            ];

        } catch (\Exception $e) {
            Log::error('[UPDATE-PRODUCT-USE-CASE] Failed to update product', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update product: ' . $e->getMessage(),
                'error_code' => 'UPDATE_FAILED',
            ];
        }
    }

    /**
     * Execute the use case by platform URL
     * 
     * @param string $productUrl The product URL
     * @param string $platform The platform (amazon or jumia)
     * @return array Result with success status and product data or error
     */
    public function executeByUrl(string $productUrl, string $platform): array
    {
        Log::info('[UPDATE-PRODUCT-USE-CASE] Starting product update by URL', [
            'url' => $productUrl,
            'platform' => $platform,
        ]);

        try {
            // Validate and create value objects
            $url = ProductUrl::fromString($productUrl);
            $platformVO = Platform::fromString($platform);

            // Find the product
            $product = ProductRepository::findByUrl($url, $platformVO);

            return $this->updateProduct($product);

        } catch (ProductNotFoundException $e) {
            Log::warning('[UPDATE-PRODUCT-USE-CASE] Product not found', [
                'url' => $productUrl,
                'platform' => $platform,
            ]);

            return [
                'success' => false,
                'error' => 'Product not found for this URL and platform',
                'error_code' => 'PRODUCT_NOT_FOUND',
            ];

        } catch (\InvalidArgumentException $e) {
            Log::warning('[UPDATE-PRODUCT-USE-CASE] Invalid input', [
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
            Log::error('[UPDATE-PRODUCT-USE-CASE] Failed to update product', [
                'url' => $productUrl,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update product: ' . $e->getMessage(),
                'error_code' => 'UPDATE_FAILED',
            ];
        }
    }

    /**
     * Execute the use case by platform ID
     * 
     * @param string $platformId The platform-specific product identifier
     * @param string $platform The platform (amazon or jumia)
     * @return array Result with success status and product data or error
     */
    public function executeByPlatformId(string $platformId, string $platform): array
    {
        Log::info('[UPDATE-PRODUCT-USE-CASE] Starting product update by platform ID', [
            'platform_id' => $platformId,
            'platform' => $platform,
        ]);

        try {
            // Validate platform
            $platformVO = Platform::fromString($platform);

            // Find product by platform_id
            // Note: We need to search through active products
            $products = ProductRepository::findActiveByPlatform($platformVO);
            $product = null;

            foreach ($products as $p) {
                if ($p->getPlatformId() === $platformId) {
                    $product = $p;
                    break;
                }
            }

            if (!$product) {
                throw new ProductNotFoundException(
                    "Product not found with platform_id '{$platformId}' for platform '{$platform}'"
                );
            }

            return $this->updateProduct($product);

        } catch (ProductNotFoundException $e) {
            Log::warning('[UPDATE-PRODUCT-USE-CASE] Product not found', [
                'platform_id' => $platformId,
                'platform' => $platform,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'PRODUCT_NOT_FOUND',
            ];

        } catch (\InvalidArgumentException $e) {
            Log::warning('[UPDATE-PRODUCT-USE-CASE] Invalid input', [
                'platform_id' => $platformId,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'INVALID_INPUT',
            ];

        } catch (\Exception $e) {
            Log::error('[UPDATE-PRODUCT-USE-CASE] Failed to update product', [
                'platform_id' => $platformId,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update product: ' . $e->getMessage(),
                'error_code' => 'UPDATE_FAILED',
            ];
        }
    }

    /**
     * Common update logic - re-scrape and update product
     */
    private function updateProduct(Product $product): array
    {
        $oldPrice = $product->getPrice()->toFloat();
        $oldRating = $product->getRating();

        // Re-scrape and update the product
        $updatedProduct = $this->scrapingStorageService->scrapeAndStore(
            $product->getProductUrl(),
            $product->getPlatform()
        );

        $priceChanged = $oldPrice !== $updatedProduct->getPrice()->toFloat();
        $ratingChanged = $oldRating !== $updatedProduct->getRating();

        Log::info('[UPDATE-PRODUCT-USE-CASE] Product updated successfully', [
            'id' => $updatedProduct->getId(),
            'title' => $updatedProduct->getTitle(),
            'old_price' => $oldPrice,
            'new_price' => $updatedProduct->getPrice()->toFloat(),
            'price_changed' => $priceChanged,
            'rating_changed' => $ratingChanged,
        ]);

        return [
            'success' => true,
            'product' => $this->formatProduct($updatedProduct),
            'changes' => [
                'price_changed' => $priceChanged,
                'old_price' => $oldPrice,
                'new_price' => $updatedProduct->getPrice()->toFloat(),
                'rating_changed' => $ratingChanged,
                'old_rating' => $oldRating,
                'new_rating' => $updatedProduct->getRating(),
            ],
            'message' => 'Product updated successfully',
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
