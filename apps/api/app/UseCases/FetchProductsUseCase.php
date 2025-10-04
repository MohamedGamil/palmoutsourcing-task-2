<?php

declare(strict_types=1);

namespace App\UseCases;

use App\Models\Product as ProductModel;
use App\Services\ProductCacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

/**
 * Fetch Products Use Case
 * 
 * Fetches products from the database in a paginated format with comprehensive
 * filtering support (platform, price range, rating, category, search, etc.).
 * Implements caching for improved performance.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-007: App layer implements use-cases for application logic
 * - REQ-API-002: GET endpoint for retrieving products
 * - REQ-FILTER-001 to REQ-FILTER-013: Filtering and pagination requirements
 * - REQ-SCALE-002: API SHALL support pagination
 * - REQ-PERF-004: System SHALL implement caching for frequently accessed data
 * - REQ-PERF-006: API responses SHALL be cached
 * 
 * @package App\UseCases
 */
class FetchProductsUseCase
{
    /**
     * Default pagination size as per REQ-FILTER-007
     */
    private const DEFAULT_PER_PAGE = 15;

    /**
     * Maximum pagination size to prevent performance issues
     */
    private const MAX_PER_PAGE = 100;

    public function __construct(
        private ProductCacheService $cacheService
    ) {}

    /**
     * Execute the fetch with filters and pagination
     * 
     * Implements caching as per REQ-PERF-006: API responses SHALL be cached
     * 
     * @param array $filters Associative array of filter criteria
     * @param int $page Current page number (1-indexed)
     * @param int $perPage Number of items per page
     * @param string $sortBy Field to sort by (default: created_at)
     * @param string $sortOrder Sort direction (asc/desc)
     * @return array Paginated result with products and metadata
     */
    public function execute(
        array $filters = [],
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
        string $sortBy = 'created_at',
        string $sortOrder = 'desc'
    ): array {
        Log::info('[FETCH-PRODUCTS-USE-CASE] Starting product fetch', [
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ]);

        try {
            // Validate and normalize pagination parameters
            $page = max(1, $page);
            $perPage = min(max(1, $perPage), self::MAX_PER_PAGE);
            $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

            // Generate cache key for this specific query
            $cacheKey = $this->cacheService->generateListIdentifier(
                $filters,
                $page,
                $perPage,
                $sortBy,
                $sortOrder
            );

            // Try to get cached result
            $cachedResult = $this->cacheService->getCachedProductList($cacheKey);
            if ($cachedResult !== null) {
                Log::info('[FETCH-PRODUCTS-USE-CASE] Returning cached result', [
                    'cache_key' => $cacheKey,
                ]);
                return $cachedResult;
            }

            // Build the query with filters
            $query = $this->buildQuery($filters);

            // Apply sorting
            $query = $this->applySorting($query, $sortBy, $sortOrder);

            // Execute paginated query
            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            // Format the response
            $result = [
                'success' => true,
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
                'filters_applied' => $this->getAppliedFilters($filters),
                'sorting' => [
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
            ];

            // Cache the result for future requests
            $this->cacheService->cacheProductList(
                $cacheKey,
                $result,
                ProductCacheService::LIST_TTL
            );

            Log::info('[FETCH-PRODUCTS-USE-CASE] Products fetched successfully', [
                'total_results' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'returned_count' => count($paginator->items()),
                'cached' => true,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('[FETCH-PRODUCTS-USE-CASE] Failed to fetch products', [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch products: ' . $e->getMessage(),
                'error_code' => 'FETCH_FAILED',
            ];
        }
    }

    /**
     * Build the query with applied filters
     * 
     * Implements REQ-FILTER-001 through REQ-FILTER-013
     */
    private function buildQuery(array $filters): Builder
    {
        $query = ProductModel::query();

        // REQ-FILTER-001: Filter by platform (amazon/jumia)
        if (!empty($filters['platform'])) {
            $query->where('platform', strtolower($filters['platform']));
        }

        // REQ-FILTER-002: Filter by price range
        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        // REQ-FILTER-003: Search in product title
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where('title', 'LIKE', "%{$searchTerm}%");
        }

        // REQ-FILTER-004: Filter by date range
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // REQ-FILTER-011: Filter by rating range
        if (isset($filters['min_rating']) && is_numeric($filters['min_rating'])) {
            $query->where('rating', '>=', (float) $filters['min_rating']);
        }

        if (isset($filters['max_rating']) && is_numeric($filters['max_rating'])) {
            $query->where('rating', '<=', (float) $filters['max_rating']);
        }

        // REQ-FILTER-012: Filter by platform category
        if (!empty($filters['category'])) {
            $query->where('platform_category', 'LIKE', "%{$filters['category']}%");
        }

        // REQ-FILTER-013: Filter by price currency
        if (!empty($filters['currency'])) {
            $query->where('price_currency', strtoupper($filters['currency']));
        }

        // Filter by platform_id
        if (!empty($filters['platform_id'])) {
            $query->where('platform_id', $filters['platform_id']);
        }

        // Filter by active status
        if (isset($filters['is_active'])) {
            $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

        // Filter by last scraped date range
        if (!empty($filters['last_scraped_from'])) {
            $query->where('last_scraped_at', '>=', $filters['last_scraped_from']);
        }

        if (!empty($filters['last_scraped_to'])) {
            $query->where('last_scraped_at', '<=', $filters['last_scraped_to']);
        }

        // Filter by scrape count
        if (isset($filters['min_scrape_count']) && is_numeric($filters['min_scrape_count'])) {
            $query->where('scrape_count', '>=', (int) $filters['min_scrape_count']);
        }

        if (isset($filters['max_scrape_count']) && is_numeric($filters['max_scrape_count'])) {
            $query->where('scrape_count', '<=', (int) $filters['max_scrape_count']);
        }

        return $query;
    }

    /**
     * Apply sorting to the query
     */
    private function applySorting(Builder $query, string $sortBy, string $sortOrder): Builder
    {
        // Whitelist of allowed sort fields to prevent SQL injection
        $allowedSortFields = [
            'id',
            'title',
            'price',
            'rating',
            'rating_count',
            'platform',
            'platform_category',
            'created_at',
            'updated_at',
            'last_scraped_at',
            'scrape_count',
            'is_active',
        ];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            // Default sorting if invalid field is provided
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    /**
     * Get a summary of applied filters
     */
    private function getAppliedFilters(array $filters): array
    {
        $applied = [];

        $filterMapping = [
            'platform' => 'Platform',
            'min_price' => 'Minimum Price',
            'max_price' => 'Maximum Price',
            'search' => 'Search Term',
            'date_from' => 'Date From',
            'date_to' => 'Date To',
            'min_rating' => 'Minimum Rating',
            'max_rating' => 'Maximum Rating',
            'category' => 'Category',
            'currency' => 'Currency',
            'platform_id' => 'Platform ID',
            'is_active' => 'Active Status',
            'last_scraped_from' => 'Last Scraped From',
            'last_scraped_to' => 'Last Scraped To',
            'min_scrape_count' => 'Minimum Scrape Count',
            'max_scrape_count' => 'Maximum Scrape Count',
        ];

        foreach ($filters as $key => $value) {
            if (!empty($value) || $value === '0' || $value === 0 || $value === false) {
                $applied[$key] = [
                    'label' => $filterMapping[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                    'value' => $value,
                ];
            }
        }

        return $applied;
    }

    /**
     * Get product statistics
     * 
     * Implements caching as per REQ-PERF-006: API responses SHALL be cached
     * Uses short TTL (5 minutes) since statistics change frequently
     * 
     * @return array Statistics about products in the database
     */
    public function getStatistics(): array
    {
        Log::info('[FETCH-PRODUCTS-USE-CASE] Fetching product statistics');

        try {
            // Try to get cached statistics
            $cachedStats = $this->cacheService->getCachedStatistics();
            if ($cachedStats !== null) {
                Log::info('[FETCH-PRODUCTS-USE-CASE] Returning cached statistics');
                return $cachedStats;
            }

            $stats = [
                'total_products' => ProductModel::count(),
                'active_products' => ProductModel::where('is_active', true)->count(),
                'inactive_products' => ProductModel::where('is_active', false)->count(),
                'by_platform' => [
                    'amazon' => ProductModel::where('platform', 'amazon')->count(),
                    'jumia' => ProductModel::where('platform', 'jumia')->count(),
                ],
                'price_stats' => [
                    'min' => ProductModel::min('price'),
                    'max' => ProductModel::max('price'),
                    'avg' => round(ProductModel::avg('price'), 2),
                ],
                'rating_stats' => [
                    'min' => ProductModel::min('rating'),
                    'max' => ProductModel::max('rating'),
                    'avg' => round(ProductModel::avg('rating'), 2),
                ],
                'scraping_stats' => [
                    'total_scrapes' => ProductModel::sum('scrape_count'),
                    'avg_scrapes_per_product' => round(ProductModel::avg('scrape_count'), 2),
                    'products_never_scraped' => ProductModel::whereNull('last_scraped_at')->count(),
                    'products_scraped_today' => ProductModel::whereDate('last_scraped_at', today())->count(),
                ],
            ];

            $result = [
                'success' => true,
                'statistics' => $stats,
            ];

            // Cache the statistics with short TTL
            $this->cacheService->cacheStatistics($result, ProductCacheService::STATS_TTL);

            Log::info('[FETCH-PRODUCTS-USE-CASE] Statistics fetched and cached successfully', $stats);

            return $result;

        } catch (\Exception $e) {
            Log::error('[FETCH-PRODUCTS-USE-CASE] Failed to fetch statistics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch statistics: ' . $e->getMessage(),
                'error_code' => 'STATS_FAILED',
            ];
        }
    }

    /**
     * Get a single product by ID
     * 
     * Implements caching as per REQ-PERF-006: API responses SHALL be cached
     * 
     * @param int $productId The product ID
     * @return array Result with product data or error
     */
    public function getById(int $productId): array
    {
        Log::info('[FETCH-PRODUCTS-USE-CASE] Fetching product by ID', [
            'product_id' => $productId,
        ]);

        try {
            // Try to get cached product
            $cachedProduct = $this->cacheService->getCachedProduct($productId);
            if ($cachedProduct !== null) {
                Log::info('[FETCH-PRODUCTS-USE-CASE] Returning cached product', [
                    'product_id' => $productId,
                ]);
                return $cachedProduct;
            }

            $product = ProductModel::findOrFail($productId);

            $result = [
                'success' => true,
                'data' => $product,
            ];

            // Cache the product
            $this->cacheService->cacheProduct(
                $productId,
                $result,
                ProductCacheService::DEFAULT_TTL
            );

            Log::info('[FETCH-PRODUCTS-USE-CASE] Product fetched and cached successfully', [
                'product_id' => $productId,
            ]);

            return $result;

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[FETCH-PRODUCTS-USE-CASE] Product not found', [
                'product_id' => $productId,
            ]);

            return [
                'success' => false,
                'error' => 'Product not found',
                'error_code' => 'PRODUCT_NOT_FOUND',
            ];

        } catch (\Exception $e) {
            Log::error('[FETCH-PRODUCTS-USE-CASE] Failed to fetch product', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch product: ' . $e->getMessage(),
                'error_code' => 'FETCH_FAILED',
            ];
        }
    }
}
