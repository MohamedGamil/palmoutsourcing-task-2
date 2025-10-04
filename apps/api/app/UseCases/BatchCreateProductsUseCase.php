<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Facades\ProductRepository;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\ProductUrl;
use Illuminate\Support\Facades\Log;

/**
 * Batch Create Products Use Case
 * 
 * Creates multiple products by scraping from given URLs (Amazon/Jumia)
 * and storing them to the database. Limited to 50 products per batch.
 * Handles partial failures gracefully.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-007: App layer implements use-cases for application logic
 * - REQ-SCRAPE-011: Support scraping a list of products from multiple URLs
 * - REQ-PERSIST-001: Store scraped product data in database
 * - Batch processing with failure handling
 * 
 * @package App\UseCases
 */
class BatchCreateProductsUseCase
{
    /**
     * Maximum products allowed per batch as per requirements
     */
    private const MAX_BATCH_SIZE = 50;

    public function __construct(
        private CreateProductUseCase $createProductUseCase
    ) {}

    /**
     * Execute the batch creation use case
     * 
     * @param array $products Array of ['url' => string, 'platform' => string]
     * @return array Result with success/failed products and summary
     */
    public function execute(array $products): array
    {
        Log::info('[BATCH-CREATE-PRODUCTS-USE-CASE] Starting batch creation', [
            'total_products' => count($products),
        ]);

        // Validate batch size
        if (count($products) > self::MAX_BATCH_SIZE) {
            Log::warning('[BATCH-CREATE-PRODUCTS-USE-CASE] Batch size exceeds limit', [
                'requested' => count($products),
                'max_allowed' => self::MAX_BATCH_SIZE,
            ]);

            return [
                'success' => false,
                'error' => sprintf(
                    'Batch size exceeds maximum limit. Maximum %d products allowed per batch, %d provided.',
                    self::MAX_BATCH_SIZE,
                    count($products)
                ),
                'error_code' => 'BATCH_SIZE_EXCEEDED',
                'max_batch_size' => self::MAX_BATCH_SIZE,
            ];
        }

        if (empty($products)) {
            Log::warning('[BATCH-CREATE-PRODUCTS-USE-CASE] Empty batch provided');

            return [
                'success' => false,
                'error' => 'No products provided',
                'error_code' => 'EMPTY_BATCH',
            ];
        }

        $results = [
            'successful' => [],
            'failed' => [],
            'skipped' => [],
        ];

        $startTime = microtime(true);

        // Process each product
        foreach ($products as $index => $productData) {
            $productNumber = $index + 1;

            // Validate product data structure
            if (!isset($productData['url']) || !isset($productData['platform'])) {
                Log::warning('[BATCH-CREATE-PRODUCTS-USE-CASE] Invalid product data', [
                    'index' => $index,
                    'data' => $productData,
                ]);

                $results['failed'][] = [
                    'index' => $index,
                    'product_number' => $productNumber,
                    'url' => $productData['url'] ?? null,
                    'platform' => $productData['platform'] ?? null,
                    'error' => 'Invalid product data: missing url or platform',
                    'error_code' => 'INVALID_DATA',
                ];
                continue;
            }

            $url = $productData['url'];
            $platform = $productData['platform'];

            Log::debug('[BATCH-CREATE-PRODUCTS-USE-CASE] Processing product', [
                'product_number' => $productNumber,
                'url' => $url,
                'platform' => $platform,
            ]);

            try {
                // Check if product already exists
                $urlVO = ProductUrl::fromString($url);
                $platformVO = Platform::fromString($platform);

                if (ProductRepository::existsByUrl($urlVO, $platformVO)) {
                    Log::info('[BATCH-CREATE-PRODUCTS-USE-CASE] Product already exists, skipping', [
                        'product_number' => $productNumber,
                        'url' => $url,
                    ]);

                    $results['skipped'][] = [
                        'index' => $index,
                        'product_number' => $productNumber,
                        'url' => $url,
                        'platform' => $platform,
                        'reason' => 'Product already exists',
                    ];
                    continue;
                }

                // Create the product
                $result = $this->createProductUseCase->execute($url, $platform);

                if ($result['success']) {
                    $results['successful'][] = [
                        'index' => $index,
                        'product_number' => $productNumber,
                        'product' => $result['product'],
                    ];

                    Log::info('[BATCH-CREATE-PRODUCTS-USE-CASE] Product created', [
                        'product_number' => $productNumber,
                        'product_id' => $result['product']['id'],
                        'title' => $result['product']['title'],
                    ]);
                } else {
                    $results['failed'][] = [
                        'index' => $index,
                        'product_number' => $productNumber,
                        'url' => $url,
                        'platform' => $platform,
                        'error' => $result['error'],
                        'error_code' => $result['error_code'],
                    ];

                    Log::warning('[BATCH-CREATE-PRODUCTS-USE-CASE] Product creation failed', [
                        'product_number' => $productNumber,
                        'url' => $url,
                        'error' => $result['error'],
                    ]);
                }

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'index' => $index,
                    'product_number' => $productNumber,
                    'url' => $url,
                    'platform' => $platform,
                    'error' => $e->getMessage(),
                    'error_code' => 'EXCEPTION',
                ];

                Log::error('[BATCH-CREATE-PRODUCTS-USE-CASE] Unexpected error processing product', [
                    'product_number' => $productNumber,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $successCount = count($results['successful']);
        $failedCount = count($results['failed']);
        $skippedCount = count($results['skipped']);
        $totalCount = count($products);

        Log::info('[BATCH-CREATE-PRODUCTS-USE-CASE] Batch creation completed', [
            'total' => $totalCount,
            'successful' => $successCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
            'duration_seconds' => $duration,
        ]);

        return [
            'success' => true,
            'summary' => [
                'total_products' => $totalCount,
                'successful' => $successCount,
                'failed' => $failedCount,
                'skipped' => $skippedCount,
                'success_rate' => $totalCount > 0 
                    ? round(($successCount / $totalCount) * 100, 2) 
                    : 0,
                'duration_seconds' => $duration,
            ],
            'results' => $results,
            'message' => sprintf(
                'Batch processing completed: %d successful, %d failed, %d skipped out of %d total',
                $successCount,
                $failedCount,
                $skippedCount,
                $totalCount
            ),
        ];
    }
}
