<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Facades\ProductRepository;
use Domain\Product\Exception\ProductNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * Delete Product Use Case
 * 
 * Deletes a product from the database after validating its existence.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-007: App layer implements use-cases for application logic
 * - REQ-API-006: DELETE endpoint for removing watched products
 * - REQ-REPO-003: Repository delete operation
 * 
 * @package App\UseCases
 */
class DeleteProductUseCase
{
    /**
     * Execute the deletion by product ID
     * 
     * @param int $productId The product ID to delete
     * @return array Result with success status or error
     */
    public function execute(int $productId): array
    {
        Log::info('[DELETE-PRODUCT-USE-CASE] Starting product deletion', [
            'product_id' => $productId,
        ]);

        try {
            // Find the product first to ensure it exists
            $product = ProductRepository::findById($productId);

            // Store product details for logging before deletion
            $productDetails = [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'platform' => $product->getPlatform()->toString(),
                'url' => $product->getProductUrl()->toString(),
                'created_at' => $product->getCreatedAt()->format('Y-m-d H:i:s'),
            ];

            // Delete the product
            ProductRepository::delete($product);

            Log::info('[DELETE-PRODUCT-USE-CASE] Product deleted successfully', $productDetails);

            return [
                'success' => true,
                'deleted_product' => $productDetails,
                'message' => 'Product deleted successfully',
            ];

        } catch (ProductNotFoundException $e) {
            Log::warning('[DELETE-PRODUCT-USE-CASE] Product not found', [
                'product_id' => $productId,
            ]);

            return [
                'success' => false,
                'error' => 'Product not found',
                'error_code' => 'PRODUCT_NOT_FOUND',
            ];

        } catch (\Exception $e) {
            Log::error('[DELETE-PRODUCT-USE-CASE] Failed to delete product', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to delete product: ' . $e->getMessage(),
                'error_code' => 'DELETION_FAILED',
            ];
        }
    }

    /**
     * Batch delete multiple products
     * 
     * @param array $productIds Array of product IDs to delete
     * @return array Result with successful and failed deletions
     */
    public function batchDelete(array $productIds): array
    {
        Log::info('[DELETE-PRODUCT-USE-CASE] Starting batch deletion', [
            'product_count' => count($productIds),
        ]);

        if (empty($productIds)) {
            return [
                'success' => false,
                'error' => 'No product IDs provided',
                'error_code' => 'EMPTY_BATCH',
            ];
        }

        $results = [
            'successful' => [],
            'failed' => [],
        ];

        foreach ($productIds as $productId) {
            $result = $this->execute($productId);

            if ($result['success']) {
                $results['successful'][] = [
                    'product_id' => $productId,
                    'deleted_product' => $result['deleted_product'],
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

        Log::info('[DELETE-PRODUCT-USE-CASE] Batch deletion completed', [
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
            ],
            'results' => $results,
            'message' => sprintf(
                'Batch deletion completed: %d successful, %d failed out of %d total',
                $successCount,
                $failedCount,
                count($productIds)
            ),
        ];
    }

    /**
     * Delete all inactive products older than specified days
     * 
     * @param int $olderThanDays Only delete inactive products older than this many days
     * @return array Result with deletion statistics
     */
    public function deleteInactiveProducts(int $olderThanDays = 30): array
    {
        Log::info('[DELETE-PRODUCT-USE-CASE] Starting deletion of old inactive products', [
            'older_than_days' => $olderThanDays,
        ]);

        try {
            // Get all products and filter inactive ones older than specified days
            $allProducts = ProductRepository::findAllActive();
            
            // Since we don't have a direct method to get inactive products,
            // we'll need to query the model directly through the repository
            // For now, we'll log a warning and return
            Log::warning('[DELETE-PRODUCT-USE-CASE] Method not fully implemented', [
                'reason' => 'Repository does not have findInactiveOlderThan method',
            ]);

            return [
                'success' => false,
                'error' => 'Feature not fully implemented. Please use batch delete with specific IDs.',
                'error_code' => 'NOT_IMPLEMENTED',
            ];

        } catch (\Exception $e) {
            Log::error('[DELETE-PRODUCT-USE-CASE] Failed to delete inactive products', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to delete inactive products: ' . $e->getMessage(),
                'error_code' => 'DELETION_FAILED',
            ];
        }
    }

    /**
     * Soft delete (deactivate) instead of hard delete
     * This is an alternative approach that preserves data
     * 
     * @param int $productId The product ID to soft delete
     * @return array Result with success status or error
     */
    public function softDelete(int $productId): array
    {
        Log::info('[DELETE-PRODUCT-USE-CASE] Starting soft deletion (deactivation)', [
            'product_id' => $productId,
        ]);

        try {
            // Find the product
            $product = ProductRepository::findById($productId);

            // Deactivate instead of deleting
            $product->deactivate();
            $updatedProduct = ProductRepository::save($product);

            Log::info('[DELETE-PRODUCT-USE-CASE] Product soft deleted (deactivated)', [
                'product_id' => $productId,
                'title' => $product->getTitle(),
            ]);

            return [
                'success' => true,
                'product' => [
                    'id' => $updatedProduct->getId(),
                    'title' => $updatedProduct->getTitle(),
                    'is_active' => $updatedProduct->isActive(),
                ],
                'message' => 'Product deactivated (soft deleted) successfully',
            ];

        } catch (ProductNotFoundException $e) {
            Log::warning('[DELETE-PRODUCT-USE-CASE] Product not found for soft delete', [
                'product_id' => $productId,
            ]);

            return [
                'success' => false,
                'error' => 'Product not found',
                'error_code' => 'PRODUCT_NOT_FOUND',
            ];

        } catch (\Exception $e) {
            Log::error('[DELETE-PRODUCT-USE-CASE] Failed to soft delete product', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to soft delete product: ' . $e->getMessage(),
                'error_code' => 'SOFT_DELETE_FAILED',
            ];
        }
    }
}
