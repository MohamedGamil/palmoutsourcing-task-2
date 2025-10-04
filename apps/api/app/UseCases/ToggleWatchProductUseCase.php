<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Facades\ProductRepository;
use Domain\Product\Entity\Product;
use Domain\Product\Exception\ProductNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * Toggle Watch Product Use Case
 * 
 * Toggles the is_active status of a product, enabling or disabling
 * active watching/monitoring of the product.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-007: App layer implements use-cases for application logic
 * - REQ-API-005: PUT/PATCH endpoint for updating products
 * - REQ-MODEL-006: Product model SHALL cast is_active as boolean
 * - REQ-WATCH-003: System SHALL periodically check watched products for updates
 * 
 * @package App\UseCases
 */
class ToggleWatchProductUseCase
{
    /**
     * Toggle product watch status by product ID
     * 
     * @param int $productId The product ID
     * @param bool|null $targetStatus Optional: specific status to set (true=activate, false=deactivate)
     *                                If null, will toggle current status
     * @return array Result with success status and product data or error
     */
    public function execute(int $productId, ?bool $targetStatus = null): array
    {
        Log::info('[TOGGLE-WATCH-PRODUCT-USE-CASE] Starting watch status toggle', [
            'product_id' => $productId,
            'target_status' => $targetStatus,
        ]);

        try {
            // Find the product
            $product = ProductRepository::findById($productId);
            $oldStatus = $product->isActive();

            // Determine new status
            $newStatus = $targetStatus !== null ? $targetStatus : !$oldStatus;

            // Update the status
            if ($newStatus === $oldStatus) {
                Log::info('[TOGGLE-WATCH-PRODUCT-USE-CASE] Status unchanged', [
                    'product_id' => $productId,
                    'status' => $oldStatus,
                ]);

                return [
                    'success' => true,
                    'product' => $this->formatProduct($product),
                    'changed' => false,
                    'message' => sprintf(
                        'Product is already %s',
                        $oldStatus ? 'active' : 'inactive'
                    ),
                ];
            }

            // Apply the status change
            if ($newStatus) {
                $product->activate();
                $action = 'activated';
            } else {
                $product->deactivate();
                $action = 'deactivated';
            }

            // Save the product
            $updatedProduct = ProductRepository::save($product);

            Log::info('[TOGGLE-WATCH-PRODUCT-USE-CASE] Watch status toggled successfully', [
                'product_id' => $productId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'action' => $action,
            ]);

            return [
                'success' => true,
                'product' => $this->formatProduct($updatedProduct),
                'changed' => true,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'message' => sprintf('Product %s successfully', $action),
            ];

        } catch (ProductNotFoundException $e) {
            Log::warning('[TOGGLE-WATCH-PRODUCT-USE-CASE] Product not found', [
                'product_id' => $productId,
            ]);

            return [
                'success' => false,
                'error' => 'Product not found',
                'error_code' => 'PRODUCT_NOT_FOUND',
            ];

        } catch (\Exception $e) {
            Log::error('[TOGGLE-WATCH-PRODUCT-USE-CASE] Failed to toggle watch status', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to toggle watch status: ' . $e->getMessage(),
                'error_code' => 'TOGGLE_FAILED',
            ];
        }
    }

    /**
     * Activate product watching by ID
     * 
     * @param int $productId The product ID
     * @return array Result with success status and product data or error
     */
    public function activate(int $productId): array
    {
        return $this->execute($productId, true);
    }

    /**
     * Deactivate product watching by ID
     * 
     * @param int $productId The product ID
     * @return array Result with success status and product data or error
     */
    public function deactivate(int $productId): array
    {
        return $this->execute($productId, false);
    }

    /**
     * Batch toggle watch status for multiple products
     * 
     * @param array $productIds Array of product IDs
     * @param bool $targetStatus Target status (true=activate, false=deactivate)
     * @return array Result with successful and failed toggles
     */
    public function batchToggle(array $productIds, bool $targetStatus): array
    {
        Log::info('[TOGGLE-WATCH-PRODUCT-USE-CASE] Starting batch toggle', [
            'product_count' => count($productIds),
            'target_status' => $targetStatus,
        ]);

        $results = [
            'successful' => [],
            'failed' => [],
        ];

        foreach ($productIds as $productId) {
            $result = $this->execute($productId, $targetStatus);

            if ($result['success']) {
                $results['successful'][] = [
                    'product_id' => $productId,
                    'changed' => $result['changed'],
                ];
            } else {
                $results['failed'][] = [
                    'product_id' => $productId,
                    'error' => $result['error'],
                    'error_code' => $result['error_code'],
                ];
            }
        }

        $successCount = count($results['successful']);
        $failedCount = count($results['failed']);

        Log::info('[TOGGLE-WATCH-PRODUCT-USE-CASE] Batch toggle completed', [
            'total' => count($productIds),
            'successful' => $successCount,
            'failed' => $failedCount,
        ]);

        return [
            'success' => true,
            'summary' => [
                'total' => count($productIds),
                'successful' => $successCount,
                'failed' => $failedCount,
                'target_status' => $targetStatus,
            ],
            'results' => $results,
            'message' => sprintf(
                'Batch toggle completed: %d successful, %d failed',
                $successCount,
                $failedCount
            ),
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
