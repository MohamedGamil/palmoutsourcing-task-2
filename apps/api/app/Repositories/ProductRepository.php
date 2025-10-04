<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product as ProductModel;
use App\Services\ProductCacheService;
use Domain\Product\Entity\Product;
use Domain\Product\Repository\ProductRepositoryInterface;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\Price;
use Domain\Product\ValueObject\ProductUrl;
use Domain\Product\Exception\ProductNotFoundException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Product Repository Implementation
 * 
 * Implements the domain ProductRepositoryInterface using Laravel's Eloquent ORM.
 * Handles mapping between domain entities and database models.
 * Implements cache invalidation as per REQ-PERF-007: Cache invalidation SHALL occur on data updates
 * 
 * Requirements Implemented:
 * - REQ-ARCH-005: App layer implements repositories based on domain layer contracts
 * - REQ-REPO-001 to REQ-REPO-006: Repository operations
 * - REQ-PERSIST-001: Store scraped product data in database
 * - REQ-PERSIST-002: Update existing products with new scraping data
 * - REQ-PERF-007: Cache invalidation SHALL occur on data updates
 */
class ProductRepository implements ProductRepositoryInterface
{
    private ProductModel $model;
    private ProductCacheService $cacheService;

    public function __construct(
        ProductModel $model,
        ProductCacheService $cacheService
    ) {
        $this->model = $model;
        $this->cacheService = $cacheService;
    }

    /**
     * Find a product by its ID
     * 
     * @throws ProductNotFoundException
     */
    public function findById(int $id): Product
    {
        $model = $this->model->find($id);
        
        if (!$model) {
            throw ProductNotFoundException::byId($id);
        }

        return $this->toDomainEntity($model);
    }

    /**
     * Find a product by ID or return null if not found
     */
    public function findByIdOrNull(int $id): ?Product
    {
        try {
            return $this->findById($id);
        } catch (ProductNotFoundException) {
            return null;
        }
    }

    /**
     * Find a product by URL and platform
     * 
     * @throws ProductNotFoundException
     */
    public function findByUrl(ProductUrl $url, Platform $platform): Product
    {
        $model = $this->model
            ->where('product_url', $url->toString())
            ->where('platform', $platform->toString())
            ->first();
        
        if (!$model) {
            throw ProductNotFoundException::byUrlAndPlatform(
                $url->toString(),
                $platform->toString()
            );
        }

        return $this->toDomainEntity($model);
    }

    /**
     * Find a product by URL and platform or return null
     */
    public function findByUrlOrNull(ProductUrl $url, Platform $platform): ?Product
    {
        try {
            return $this->findByUrl($url, $platform);
        } catch (ProductNotFoundException) {
            return null;
        }
    }

    /**
     * Save a product (create or update)
     * 
     * Implements cache invalidation as per REQ-PERF-007
     * 
     * @return Product The saved product with updated ID and timestamps
     */
    public function save(Product $product): Product
    {
        $isNew = $product->isNew();
        $productId = $product->getId();
        
        if ($isNew) {
            $model = $this->createNewModel($product);
            $productId = $model->id;
            
            Log::info('[PRODUCT-REPOSITORY] Created new product', [
                'id' => $model->id,
                'title' => $product->getTitle(),
                'platform' => $product->getPlatform()->toString(),
                'url' => $product->getProductUrl()->toString(),
            ]);
        } else {
            $model = $this->updateExistingModel($product);
            
            Log::info('[PRODUCT-REPOSITORY] Updated existing product', [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'price' => $product->getPrice()->toFloat(),
                'scrape_count' => $product->getScrapeCount(),
            ]);
        }

        // Invalidate cache for this product and all related caches
        // This ensures fresh data is returned on next request
        if ($productId) {
            $this->cacheService->invalidateProductComplete($productId);
            Log::debug('[PRODUCT-REPOSITORY] Cache invalidated for product', [
                'product_id' => $productId,
            ]);
        }

        return $this->toDomainEntity($model);
    }

    /**
     * Delete a product
     * 
     * Implements cache invalidation as per REQ-PERF-007
     */
    public function delete(Product $product): void
    {
        if ($product->isNew()) {
            Log::warning('[PRODUCT-REPOSITORY] Attempted to delete unsaved product', [
                'title' => $product->getTitle(),
                'url' => $product->getProductUrl()->toString(),
            ]);
            return;
        }

        $productId = $product->getId();
        $model = $this->model->find($productId);
        
        if ($model) {
            $model->delete();
            
            // Invalidate cache for deleted product
            $this->cacheService->invalidateProductComplete($productId);
            
            Log::info('[PRODUCT-REPOSITORY] Deleted product and invalidated cache', [
                'id' => $productId,
                'title' => $product->getTitle(),
            ]);
        }
    }

    /**
     * Find all active products
     * 
     * @return Product[]
     */
    public function findAllActive(): array
    {
        $models = $this->model->active()->get();
        
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    /**
     * Find active products by platform
     * 
     * @return Product[]
     */
    public function findActiveByPlatform(Platform $platform): array
    {
        $models = $this->model
            ->active()
            ->fromPlatform($platform->toString())
            ->get();
        
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    /**
     * Find products that need scraping
     * 
     * @param int $maxHoursSinceLastScrape Hours since last scrape to consider "needs scraping"
     * @return Product[]
     */
    public function findProductsNeedingScraping(int $maxHoursSinceLastScrape = 24): array
    {
        $models = $this->model->needsUpdate($maxHoursSinceLastScrape)->get();
        
        Log::debug('[PRODUCT-REPOSITORY] Found products needing scraping', [
            'count' => $models->count(),
            'max_hours_since_last_scrape' => $maxHoursSinceLastScrape,
        ]);
        
        return $models->map(fn($model) => $this->toDomainEntity($model))->toArray();
    }

    /**
     * Count total products
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Count active products
     */
    public function countActive(): int
    {
        return $this->model->active()->count();
    }

    /**
     * Count products by platform
     */
    public function countByPlatform(Platform $platform): int
    {
        return $this->model->fromPlatform($platform->toString())->count();
    }

    /**
     * Check if a product exists by URL and platform
     */
    public function existsByUrl(ProductUrl $url, Platform $platform): bool
    {
        return $this->model
            ->where('product_url', $url->toString())
            ->where('platform', $platform->toString())
            ->exists();
    }

    /**
     * Create a new model from domain entity
     */
    private function createNewModel(Product $product): ProductModel
    {
        $data = [
            'title' => $product->getTitle(),
            'price' => $product->getPrice()->toFloat(),
            'price_currency' => $product->getPriceCurrency(),
            'rating' => $product->getRating(),
            'rating_count' => $product->getRatingCount(),
            'platform_category' => $product->getPlatformCategory(),
            'image_url' => $product->getImageUrl(),
            'product_url' => $product->getProductUrl()->toString(),
            'platform' => $product->getPlatform()->toString(),
            'platform_id' => $product->getPlatformId(),
            'last_scraped_at' => $product->getLastScrapedAt() 
                ? Carbon::parse($product->getLastScrapedAt()) 
                : null,
            'scrape_count' => $product->getScrapeCount(),
            'is_active' => $product->isActive(),
        ];

        return $this->model->create($data);
    }

    /**
     * Update existing model from domain entity
     */
    private function updateExistingModel(Product $product): ProductModel
    {
        $model = $this->model->findOrFail($product->getId());
        
        $data = [
            'title' => $product->getTitle(),
            'price' => $product->getPrice()->toFloat(),
            'price_currency' => $product->getPriceCurrency(),
            'rating' => $product->getRating(),
            'rating_count' => $product->getRatingCount(),
            'platform_category' => $product->getPlatformCategory(),
            'image_url' => $product->getImageUrl(),
            'product_url' => $product->getProductUrl()->toString(),
            'platform' => $product->getPlatform()->toString(),
            'platform_id' => $product->getPlatformId(),
            'last_scraped_at' => $product->getLastScrapedAt() 
                ? Carbon::parse($product->getLastScrapedAt()) 
                : null,
            'scrape_count' => $product->getScrapeCount(),
            'is_active' => $product->isActive(),
        ];

        $model->update($data);
        $model->refresh();
        
        return $model;
    }

    /**
     * Convert database model to domain entity
     */
    private function toDomainEntity(ProductModel $model): Product
    {
        return Product::reconstitute(
            id: $model->id,
            title: $model->title,
            price: Price::fromFloat($model->price),
            productUrl: ProductUrl::fromString($model->product_url),
            platform: Platform::fromString($model->platform),
            priceCurrency: $model->price_currency ?? 'USD',
            rating: (float) ($model->rating ?? 0.0),
            ratingCount: (int) ($model->rating_count ?? 0),
            platformId: $model->platform_id,
            platformCategory: $model->platform_category,
            imageUrl: $model->image_url,
            lastScrapedAt: $model->last_scraped_at,
            scrapeCount: (int) ($model->scrape_count ?? 0),
            isActive: $model->is_active ?? true,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
