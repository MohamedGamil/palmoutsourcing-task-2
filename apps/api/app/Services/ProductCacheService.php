<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Product Cache Service
 * 
 * Centralized caching service for product-related operations.
 * Implements caching strategies and invalidation logic.
 * 
 * Requirements Implemented:
 * - REQ-PERF-004: System SHALL implement caching for frequently accessed data
 * - REQ-PERF-006: API responses SHALL be cached using Redis or similar caching mechanism
 * - REQ-PERF-007: Cache invalidation SHALL occur on data updates
 * 
 * @package App\Services
 */
class ProductCacheService
{
    /**
     * Cache key prefix for products
     */
    public const CACHE_PREFIX = 'product';

    /**
     * Default cache TTL in seconds (1 hour)
     */
    public const DEFAULT_TTL = 3600;

    /**
     * Cache TTL for product lists (30 minutes)
     */
    public const LIST_TTL = 1800;

    /**
     * Cache TTL for statistics (5 minutes)
     */
    public const STATS_TTL = 300;

    /**
     * Cache a product by ID
     * 
     * @param int $productId
     * @param mixed $data
     * @param int|null $ttl Time to live in seconds
     * @return mixed
     */
    public function cacheProduct(int $productId, mixed $data, ?int $ttl = null): mixed
    {
        $key = $this->getProductKey($productId);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        Cache::put($key, $data, $ttl);

        Log::debug('[PRODUCT-CACHE] Cached product', [
            'product_id' => $productId,
            'key' => $key,
            'ttl' => $ttl,
        ]);

        return $data;
    }

    /**
     * Get cached product by ID
     * 
     * @param int $productId
     * @return mixed|null
     */
    public function getCachedProduct(int $productId): mixed
    {
        $key = $this->getProductKey($productId);
        $data = Cache::get($key);

        if ($data !== null) {
            Log::debug('[PRODUCT-CACHE] Cache hit for product', [
                'product_id' => $productId,
                'key' => $key,
            ]);
        }

        return $data;
    }

    /**
     * Cache product list with filters
     * 
     * @param string $cacheKey
     * @param mixed $data
     * @param int|null $ttl
     * @return mixed
     */
    public function cacheProductList(string $cacheKey, mixed $data, ?int $ttl = null): mixed
    {
        $key = $this->getListKey($cacheKey);
        $ttl = $ttl ?? self::LIST_TTL;

        Cache::put($key, $data, $ttl);

        Log::debug('[PRODUCT-CACHE] Cached product list', [
            'cache_key' => $cacheKey,
            'key' => $key,
            'ttl' => $ttl,
        ]);

        return $data;
    }

    /**
     * Get cached product list
     * 
     * @param string $cacheKey
     * @return mixed|null
     */
    public function getCachedProductList(string $cacheKey): mixed
    {
        $key = $this->getListKey($cacheKey);
        $data = Cache::get($key);

        if ($data !== null) {
            Log::debug('[PRODUCT-CACHE] Cache hit for product list', [
                'cache_key' => $cacheKey,
                'key' => $key,
            ]);
        }

        return $data;
    }

    /**
     * Cache statistics
     * 
     * @param mixed $data
     * @param int|null $ttl
     * @return mixed
     */
    public function cacheStatistics(mixed $data, ?int $ttl = null): mixed
    {
        $key = $this->getStatsKey();
        $ttl = $ttl ?? self::STATS_TTL;

        Cache::put($key, $data, $ttl);

        Log::debug('[PRODUCT-CACHE] Cached statistics', [
            'key' => $key,
            'ttl' => $ttl,
        ]);

        return $data;
    }

    /**
     * Get cached statistics
     * 
     * @return mixed|null
     */
    public function getCachedStatistics(): mixed
    {
        $key = $this->getStatsKey();
        $data = Cache::get($key);

        if ($data !== null) {
            Log::debug('[PRODUCT-CACHE] Cache hit for statistics', [
                'key' => $key,
            ]);
        }

        return $data;
    }

    /**
     * Invalidate cache for a specific product
     * 
     * @param int $productId
     * @return void
     */
    public function invalidateProduct(int $productId): void
    {
        $key = $this->getProductKey($productId);
        Cache::forget($key);

        Log::info('[PRODUCT-CACHE] Invalidated product cache', [
            'product_id' => $productId,
            'key' => $key,
        ]);
    }

    /**
     * Invalidate all product list caches
     * 
     * @return void
     */
    public function invalidateAllLists(): void
    {
        // Since we can't easily iterate all list keys, we'll use tags if available
        // or clear by pattern. For simplicity, we'll use a wildcard approach.
        
        // Note: This requires Redis driver for pattern-based deletion
        $pattern = self::CACHE_PREFIX . ':list:*';
        
        try {
            Cache::flush(); // In production, use more selective clearing
            
            Log::info('[PRODUCT-CACHE] Invalidated all product list caches', [
                'pattern' => $pattern,
            ]);
        } catch (\Exception $e) {
            Log::warning('[PRODUCT-CACHE] Failed to invalidate list caches', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate statistics cache
     * 
     * @return void
     */
    public function invalidateStatistics(): void
    {
        $key = $this->getStatsKey();
        Cache::forget($key);

        Log::info('[PRODUCT-CACHE] Invalidated statistics cache', [
            'key' => $key,
        ]);
    }

    /**
     * Invalidate all caches for a product (product + lists + stats)
     * 
     * @param int $productId
     * @return void
     */
    public function invalidateProductComplete(int $productId): void
    {
        $this->invalidateProduct($productId);
        $this->invalidateAllLists();
        $this->invalidateStatistics();

        Log::info('[PRODUCT-CACHE] Complete cache invalidation', [
            'product_id' => $productId,
        ]);
    }

    /**
     * Invalidate all product-related caches
     * 
     * @return void
     */
    public function invalidateAll(): void
    {
        Cache::flush();

        Log::info('[PRODUCT-CACHE] Invalidated all caches');
    }

    /**
     * Generate cache key for a product
     * 
     * @param int $productId
     * @return string
     */
    private function getProductKey(int $productId): string
    {
        return self::CACHE_PREFIX . ':product:' . $productId;
    }

    /**
     * Generate cache key for a product list
     * 
     * @param string $identifier
     * @return string
     */
    private function getListKey(string $identifier): string
    {
        return self::CACHE_PREFIX . ':list:' . md5($identifier);
    }

    /**
     * Generate cache key for statistics
     * 
     * @return string
     */
    private function getStatsKey(): string
    {
        return self::CACHE_PREFIX . ':stats';
    }

    /**
     * Generate cache identifier from filters
     * 
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortOrder
     * @return string
     */
    public function generateListIdentifier(
        array $filters,
        int $page,
        int $perPage,
        string $sortBy,
        string $sortOrder
    ): string {
        return json_encode([
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Check if caching is enabled
     * 
     * @return bool
     */
    public function isCachingEnabled(): bool
    {
        return config('cache.default') !== 'null' && config('cache.default') !== 'array';
    }
}
