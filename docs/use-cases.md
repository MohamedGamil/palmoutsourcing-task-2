# Use Cases Implementation Guide

## Overview

This document provides comprehensive documentation for all implemented Use Cases in the Product Watching System. Use Cases represent application-specific business logic that orchestrates domain entities, services, and repositories to fulfill user requirements.

## Architecture Context

Use Cases are part of the **Application Layer** and implement the following architectural requirements:

- **REQ-ARCH-007**: App layer implements use-cases for application logic
- **REQ-ARCH-008**: Controllers utilize use-cases and remain thin
- **REQ-TEST-003**: Use-cases SHALL be testable independently of HTTP layer

### Layer Separation

```
HTTP Layer (Controllers)
        ↓
Application Layer (Use Cases) ← YOU ARE HERE
        ↓
Domain Layer (Entities, Value Objects, Services)
        ↓
Infrastructure Layer (Repositories, External Services)
```

---

## Implemented Use Cases

### 1. CreateProductUseCase

**Purpose**: Scrapes a product from a given URL (Amazon or Jumia) and stores it to the database if successful.

**Location**: `apps/api/app/UseCases/CreateProductUseCase.php`

#### Requirements Implemented
- REQ-API-003: POST endpoint for creating new watched products
- REQ-SCRAPE-010: Support scraping a single product by URL
- REQ-PERSIST-001: Store scraped product data in database
- REQ-VAL-001 to REQ-VAL-014: Data validation requirements

#### Method Signature

```php
public function execute(string $productUrl, string $platform): array
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$productUrl` | string | The product URL to scrape (Amazon or Jumia) |
| `$platform` | string | The platform identifier ('amazon' or 'jumia') |

#### Return Value

```php
// Success Response
[
    'success' => true,
    'product' => [
        'id' => 1,
        'title' => 'Product Name',
        'price' => 99.99,
        'price_currency' => 'USD',
        'rating' => 4.5,
        'rating_count' => 1250,
        'image_url' => 'https://...',
        'product_url' => 'https://...',
        'platform' => 'amazon',
        'platform_id' => 'B08N5WRWNW',
        'platform_category' => 'Electronics',
        'last_scraped_at' => '2025-10-04 12:00:00',
        'scrape_count' => 1,
        'is_active' => true,
        'created_at' => '2025-10-04 12:00:00',
        'updated_at' => '2025-10-04 12:00:00',
    ],
    'message' => 'Product created and scraped successfully'
]

// Error Response
[
    'success' => false,
    'error' => 'Error message',
    'error_code' => 'ERROR_CODE' // PRODUCT_ALREADY_EXISTS, INVALID_INPUT, CREATION_FAILED
]
```

#### Usage Example

```php
use App\UseCases\CreateProductUseCase;

$useCase = app(CreateProductUseCase::class);

$result = $useCase->execute(
    productUrl: 'https://www.amazon.com/dp/B08N5WRWNW',
    platform: 'amazon'
);

if ($result['success']) {
    $product = $result['product'];
    echo "Created product: {$product['title']} (ID: {$product['id']})";
} else {
    echo "Error: {$result['error']}";
}
```

#### Error Codes

- `PRODUCT_ALREADY_EXISTS`: Product with this URL and platform already exists
- `INVALID_INPUT`: Invalid URL or platform parameter
- `CREATION_FAILED`: Scraping or storage failed

---

### 2. UpdateProductUseCase

**Purpose**: Checks if a product exists by ID, platform_id, or platform_url, then re-scrapes and updates it with fresh data.

**Location**: `apps/api/app/UseCases/UpdateProductUseCase.php`

#### Requirements Implemented
- REQ-API-005: PUT/PATCH endpoint for updating products
- REQ-PERSIST-002: Update existing products with new scraping data
- REQ-SCRAPE-010: Support scraping a single product by URL

#### Method Signatures

```php
public function executeById(int $productId): array
public function executeByUrl(string $productUrl, string $platform): array
public function executeByPlatformId(string $platformId, string $platform): array
```

#### Parameters

**executeById:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$productId` | int | The product ID to update |

**executeByUrl:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$productUrl` | string | The product URL |
| `$platform` | string | The platform ('amazon' or 'jumia') |

**executeByPlatformId:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$platformId` | string | Platform-specific product identifier (ASIN, SKU) |
| `$platform` | string | The platform ('amazon' or 'jumia') |

#### Return Value

```php
// Success Response
[
    'success' => true,
    'product' => [...], // Full product data
    'changes' => [
        'price_changed' => true,
        'old_price' => 99.99,
        'new_price' => 89.99,
        'rating_changed' => false,
        'old_rating' => 4.5,
        'new_rating' => 4.5,
    ],
    'message' => 'Product updated successfully'
]
```

#### Usage Example

```php
use App\UseCases\UpdateProductUseCase;

$useCase = app(UpdateProductUseCase::class);

// Update by ID
$result = $useCase->executeById(1);

// Update by URL
$result = $useCase->executeByUrl(
    'https://www.amazon.com/dp/B08N5WRWNW',
    'amazon'
);

// Update by Platform ID
$result = $useCase->executeByPlatformId('B08N5WRWNW', 'amazon');

if ($result['success'] && $result['changes']['price_changed']) {
    echo "Price changed from {$result['changes']['old_price']} to {$result['changes']['new_price']}";
}
```

#### Error Codes

- `PRODUCT_NOT_FOUND`: Product does not exist
- `INVALID_INPUT`: Invalid parameters
- `UPDATE_FAILED`: Re-scraping or update failed

---

### 3. BatchCreateProductsUseCase

**Purpose**: Creates multiple products by scraping from given URLs, limited to 50 products per batch. Handles partial failures gracefully.

**Location**: `apps/api/app/UseCases/BatchCreateProductsUseCase.php`

#### Requirements Implemented
- REQ-SCRAPE-011: Support scraping a list of products from multiple URLs
- REQ-PERSIST-001: Store scraped product data in database
- Batch processing with failure handling (max 50 products)

#### Method Signature

```php
public function execute(array $products): array
```

#### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$products` | array | Array of `['url' => string, 'platform' => string]` (max 50) |

#### Return Value

```php
[
    'success' => true,
    'summary' => [
        'total_products' => 10,
        'successful' => 8,
        'failed' => 1,
        'skipped' => 1,
        'success_rate' => 80.0,
        'duration_seconds' => 45.23,
    ],
    'results' => [
        'successful' => [
            [
                'index' => 0,
                'product_number' => 1,
                'product' => [...],
            ],
            // ...
        ],
        'failed' => [
            [
                'index' => 5,
                'product_number' => 6,
                'url' => 'https://...',
                'platform' => 'amazon',
                'error' => 'Error message',
                'error_code' => 'ERROR_CODE',
            ],
        ],
        'skipped' => [
            [
                'index' => 3,
                'product_number' => 4,
                'url' => 'https://...',
                'platform' => 'jumia',
                'reason' => 'Product already exists',
            ],
        ],
    ],
    'message' => 'Batch processing completed: 8 successful, 1 failed, 1 skipped out of 10 total'
]
```

#### Usage Example

```php
use App\UseCases\BatchCreateProductsUseCase;

$useCase = app(BatchCreateProductsUseCase::class);

$products = [
    ['url' => 'https://www.amazon.com/dp/B08N5WRWNW', 'platform' => 'amazon'],
    ['url' => 'https://www.jumia.com.eg/product-1.html', 'platform' => 'jumia'],
    ['url' => 'https://www.amazon.com/dp/B07XJ8C8F5', 'platform' => 'amazon'],
    // ... up to 50 products
];

$result = $useCase->execute($products);

echo "Success rate: {$result['summary']['success_rate']}%\n";
echo "Duration: {$result['summary']['duration_seconds']} seconds\n";

foreach ($result['results']['failed'] as $failure) {
    echo "Failed product #{$failure['product_number']}: {$failure['error']}\n";
}
```

#### Error Codes

- `BATCH_SIZE_EXCEEDED`: More than 50 products provided
- `EMPTY_BATCH`: No products provided
- `INVALID_DATA`: Product data missing url or platform

---

### 4. ToggleWatchProductUseCase

**Purpose**: Toggles the `is_active` status of a product, enabling or disabling active watching/monitoring.

**Location**: `apps/api/app/UseCases/ToggleWatchProductUseCase.php`

#### Requirements Implemented
- REQ-API-005: PUT/PATCH endpoint for updating products
- REQ-MODEL-006: Product model SHALL cast is_active as boolean
- REQ-WATCH-003: System SHALL periodically check watched products for updates

#### Method Signatures

```php
public function execute(int $productId, ?bool $targetStatus = null): array
public function activate(int $productId): array
public function deactivate(int $productId): array
public function batchToggle(array $productIds, bool $targetStatus): array
```

#### Parameters

**execute:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$productId` | int | The product ID |
| `$targetStatus` | bool\|null | Optional: specific status to set (true=activate, false=deactivate). If null, toggles current status |

**batchToggle:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$productIds` | array | Array of product IDs |
| `$targetStatus` | bool | Target status (true=activate, false=deactivate) |

#### Return Value

```php
// Single Toggle
[
    'success' => true,
    'product' => [...],
    'changed' => true,
    'old_status' => false,
    'new_status' => true,
    'message' => 'Product activated successfully'
]

// Batch Toggle
[
    'success' => true,
    'summary' => [
        'total' => 5,
        'successful' => 4,
        'failed' => 1,
        'target_status' => true,
    ],
    'results' => [
        'successful' => [...],
        'failed' => [...],
    ],
    'message' => 'Batch toggle completed: 4 successful, 1 failed'
]
```

#### Usage Example

```php
use App\UseCases\ToggleWatchProductUseCase;

$useCase = app(ToggleWatchProductUseCase::class);

// Toggle (flip current status)
$result = $useCase->execute(1);

// Activate specifically
$result = $useCase->activate(1);

// Deactivate specifically
$result = $useCase->deactivate(1);

// Batch activate multiple products
$result = $useCase->batchToggle([1, 2, 3, 4, 5], true);
```

#### Error Codes

- `PRODUCT_NOT_FOUND`: Product does not exist
- `TOGGLE_FAILED`: Failed to update status

---

### 5. ScrapeProductUseCase

**Purpose**: Manually triggers the scraping process for existing products, updating them with fresh data from e-commerce platforms.

**Location**: `apps/api/app/UseCases/ScrapeProductUseCase.php`

#### Requirements Implemented
- REQ-API-007: Manual scrape trigger endpoint
- REQ-SCRAPE-010: Support scraping a single product by URL
- REQ-PERSIST-002: Update existing products with new scraping data

#### Method Signatures

```php
public function execute(int $productId): array
public function batchScrape(array $productIds): array
public function scrapeProductsNeedingUpdate(int $maxHoursSinceLastScrape = 24): array
```

#### Parameters

**execute:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$productId` | int | The product ID to scrape |

**batchScrape:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$productIds` | array | Array of product IDs |

**scrapeProductsNeedingUpdate:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$maxHoursSinceLastScrape` | int | Hours since last scrape to consider "needs scraping" (default: 24) |

#### Return Value

```php
// Single Scrape
[
    'success' => true,
    'product' => [...],
    'changes' => [
        'price_changed' => true,
        'old_price' => 99.99,
        'new_price' => 89.99,
        'price_difference' => -10.00,
        'rating_changed' => false,
        'old_rating' => 4.5,
        'new_rating' => 4.5,
        'scrape_count_before' => 5,
        'scrape_count_after' => 6,
    ],
    'message' => 'Product scraped and updated successfully'
]

// Batch Scrape
[
    'success' => true,
    'summary' => [
        'total' => 10,
        'successful' => 9,
        'failed' => 1,
        'success_rate' => 90.0,
        'duration_seconds' => 45.67,
    ],
    'results' => [
        'successful' => [...],
        'failed' => [...],
    ],
    'message' => 'Batch scraping completed: 9 successful, 1 failed out of 10 total'
]
```

#### Usage Example

```php
use App\UseCases\ScrapeProductUseCase;

$useCase = app(ScrapeProductUseCase::class);

// Scrape single product
$result = $useCase->execute(1);

if ($result['success'] && $result['changes']['price_changed']) {
    $priceDiff = $result['changes']['price_difference'];
    echo "Price " . ($priceDiff < 0 ? "dropped" : "increased") . " by $" . abs($priceDiff);
}

// Batch scrape specific products
$result = $useCase->batchScrape([1, 2, 3, 4, 5]);

// Scrape all products that haven't been scraped in 24 hours
$result = $useCase->scrapeProductsNeedingUpdate(24);
```

#### Error Codes

- `PRODUCT_NOT_FOUND`: Product does not exist
- `PRODUCT_INACTIVE`: Cannot scrape inactive products
- `SCRAPE_FAILED`: Scraping operation failed
- `BATCH_SCRAPE_FAILED`: Batch scraping failed

---

### 6. DeleteProductUseCase

**Purpose**: Deletes a product from the database after validating its existence. Supports both hard delete and soft delete (deactivation).

**Location**: `apps/api/app/UseCases/DeleteProductUseCase.php`

#### Requirements Implemented
- REQ-API-006: DELETE endpoint for removing watched products
- REQ-REPO-003: Repository delete operation

#### Method Signatures

```php
public function execute(int $productId): array
public function batchDelete(array $productIds): array
public function softDelete(int $productId): array
```

#### Parameters

**execute:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$productId` | int | The product ID to delete |

**batchDelete:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$productIds` | array | Array of product IDs to delete |

**softDelete:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$productId` | int | The product ID to soft delete (deactivate) |

#### Return Value

```php
// Single Delete
[
    'success' => true,
    'deleted_product' => [
        'id' => 1,
        'title' => 'Product Name',
        'platform' => 'amazon',
        'url' => 'https://...',
        'created_at' => '2025-10-04 12:00:00',
    ],
    'message' => 'Product deleted successfully'
]

// Batch Delete
[
    'success' => true,
    'summary' => [
        'total' => 5,
        'successful' => 4,
        'failed' => 1,
    ],
    'results' => [
        'successful' => [...],
        'failed' => [...],
    ],
    'message' => 'Batch deletion completed: 4 successful, 1 failed out of 5 total'
]

// Soft Delete
[
    'success' => true,
    'product' => [
        'id' => 1,
        'title' => 'Product Name',
        'is_active' => false,
    ],
    'message' => 'Product deactivated (soft deleted) successfully'
]
```

#### Usage Example

```php
use App\UseCases\DeleteProductUseCase;

$useCase = app(DeleteProductUseCase::class);

// Hard delete
$result = $useCase->execute(1);

// Batch delete
$result = $useCase->batchDelete([1, 2, 3]);

// Soft delete (deactivate instead of deleting)
$result = $useCase->softDelete(1);
```

#### Error Codes

- `PRODUCT_NOT_FOUND`: Product does not exist
- `DELETION_FAILED`: Failed to delete product
- `EMPTY_BATCH`: No product IDs provided
- `SOFT_DELETE_FAILED`: Failed to deactivate product

---

### 7. FetchProductsUseCase

**Purpose**: Fetches products from the database in a paginated format with comprehensive filtering support (platform, price range, rating, category, search, etc.).

**Location**: `apps/api/app/UseCases/FetchProductsUseCase.php`

#### Requirements Implemented
- REQ-API-002: GET endpoint for retrieving products
- REQ-FILTER-001 to REQ-FILTER-013: Filtering and pagination requirements
- REQ-SCALE-002: API SHALL support pagination

#### Method Signatures

```php
public function execute(
    array $filters = [],
    int $page = 1,
    int $perPage = 15,
    string $sortBy = 'created_at',
    string $sortOrder = 'desc'
): array

public function getStatistics(): array
public function getById(int $productId): array
```

#### Parameters

**execute:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `$filters` | array | Associative array of filter criteria (see below) |
| `$page` | int | Current page number (1-indexed, default: 1) |
| `$perPage` | int | Items per page (1-100, default: 15) |
| `$sortBy` | string | Field to sort by (default: 'created_at') |
| `$sortOrder` | string | Sort direction ('asc' or 'desc', default: 'desc') |

#### Available Filters

| Filter Key | Type | Description | Requirement |
|------------|------|-------------|-------------|
| `platform` | string | Filter by platform ('amazon' or 'jumia') | REQ-FILTER-001 |
| `min_price` | float | Minimum price | REQ-FILTER-002 |
| `max_price` | float | Maximum price | REQ-FILTER-002 |
| `search` | string | Search in product title | REQ-FILTER-003 |
| `date_from` | string | Created from date (Y-m-d) | REQ-FILTER-004 |
| `date_to` | string | Created to date (Y-m-d) | REQ-FILTER-004 |
| `min_rating` | float | Minimum rating (0-5) | REQ-FILTER-011 |
| `max_rating` | float | Maximum rating (0-5) | REQ-FILTER-011 |
| `category` | string | Platform category | REQ-FILTER-012 |
| `currency` | string | Price currency (ISO 4217) | REQ-FILTER-013 |
| `platform_id` | string | Platform-specific ID | - |
| `is_active` | bool | Active status | - |
| `last_scraped_from` | string | Last scraped from date | - |
| `last_scraped_to` | string | Last scraped to date | - |
| `min_scrape_count` | int | Minimum scrape count | - |
| `max_scrape_count` | int | Maximum scrape count | - |

#### Sortable Fields

- `id`, `title`, `price`, `rating`, `rating_count`
- `platform`, `platform_category`
- `created_at`, `updated_at`, `last_scraped_at`
- `scrape_count`, `is_active`

#### Return Value

```php
[
    'success' => true,
    'data' => [
        // Array of product objects
        [...],
        [...],
    ],
    'meta' => [
        'current_page' => 1,
        'per_page' => 15,
        'total' => 100,
        'last_page' => 7,
        'from' => 1,
        'to' => 15,
    ],
    'filters_applied' => [
        'platform' => [
            'label' => 'Platform',
            'value' => 'amazon',
        ],
        // ... other applied filters
    ],
    'sorting' => [
        'sort_by' => 'created_at',
        'sort_order' => 'desc',
    ]
]
```

#### Usage Examples

```php
use App\UseCases\FetchProductsUseCase;

$useCase = app(FetchProductsUseCase::class);

// Basic fetch with pagination
$result = $useCase->execute(
    filters: [],
    page: 1,
    perPage: 20
);

// Fetch Amazon products with price filter
$result = $useCase->execute(
    filters: [
        'platform' => 'amazon',
        'min_price' => 50.00,
        'max_price' => 200.00,
    ],
    page: 1,
    perPage: 15
);

// Search products by title
$result = $useCase->execute(
    filters: ['search' => 'headphones'],
    page: 1,
    perPage: 15,
    sortBy: 'price',
    sortOrder: 'asc'
);

// Complex filtering
$result = $useCase->execute(
    filters: [
        'platform' => 'jumia',
        'category' => 'Electronics',
        'min_rating' => 4.0,
        'currency' => 'EGP',
        'is_active' => true,
        'date_from' => '2025-01-01',
    ],
    page: 2,
    perPage: 25,
    sortBy: 'rating',
    sortOrder: 'desc'
);

// Get statistics
$stats = $useCase->getStatistics();
echo "Total products: {$stats['statistics']['total_products']}";
echo "Average price: {$stats['statistics']['price_stats']['avg']}";

// Get single product
$product = $useCase->getById(1);
```

#### Error Codes

- `FETCH_FAILED`: Failed to fetch products
- `PRODUCT_NOT_FOUND`: Product not found (for getById)
- `STATS_FAILED`: Failed to fetch statistics

---

## Integration with Controllers

Use Cases are designed to be called from controllers, keeping controllers thin and testable:

```php
use App\Http\Controllers\Controller;
use App\UseCases\CreateProductUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function store(
        Request $request,
        CreateProductUseCase $createProduct
    ): JsonResponse {
        $validated = $request->validate([
            'product_url' => 'required|url',
            'platform' => 'required|in:amazon,jumia',
        ]);

        $result = $createProduct->execute(
            $validated['product_url'],
            $validated['platform']
        );

        if ($result['success']) {
            return response()->json($result, 201);
        }

        return response()->json($result, 422);
    }
}
```

## Dependency Injection

All Use Cases support constructor injection and are automatically resolved by Laravel's service container:

```php
// In a controller
public function __construct(
    private CreateProductUseCase $createProduct,
    private UpdateProductUseCase $updateProduct,
    private FetchProductsUseCase $fetchProducts
) {}

// Or resolve dynamically
$useCase = app(CreateProductUseCase::class);
```

## Testing Use Cases

Use Cases are designed to be testable in isolation:

```php
use Tests\TestCase;
use App\UseCases\CreateProductUseCase;
use Domain\Product\Service\ProductScrapingStorageServiceInterface;

class CreateProductUseCaseTest extends TestCase
{
    public function test_creates_product_successfully(): void
    {
        // Mock dependencies
        $mockService = $this->mock(ProductScrapingStorageServiceInterface::class);
        
        // Setup expectations
        $mockService->shouldReceive('scrapeAndStore')
            ->once()
            ->andReturn($this->createMockProduct());

        // Execute use case
        $useCase = new CreateProductUseCase($mockService);
        $result = $useCase->execute('https://amazon.com/dp/B123', 'amazon');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('product', $result);
    }
}
```

## Error Handling Best Practices

All Use Cases follow consistent error handling patterns:

1. **Try-Catch Blocks**: Wrap operations in try-catch
2. **Logging**: Log all errors with context
3. **Consistent Response Format**: Always return array with 'success' key
4. **Error Codes**: Provide machine-readable error codes
5. **User-Friendly Messages**: Include human-readable error messages

## Performance Considerations

- **Batch Operations**: Use batch methods for bulk operations
- **Pagination**: Always paginate large result sets (FetchProductsUseCase)
- **Lazy Loading**: Avoid N+1 queries in repository layer
- **Caching**: Consider caching for frequently accessed data
- **Async Processing**: Use queues for long-running operations

## Requirements Traceability Matrix

| Use Case | Requirements Implemented |
|----------|-------------------------|
| CreateProductUseCase | REQ-API-003, REQ-SCRAPE-010, REQ-PERSIST-001 |
| UpdateProductUseCase | REQ-API-005, REQ-PERSIST-002, REQ-SCRAPE-010 |
| BatchCreateProductsUseCase | REQ-SCRAPE-011, REQ-PERSIST-001 |
| ToggleWatchProductUseCase | REQ-API-005, REQ-MODEL-006, REQ-WATCH-003 |
| ScrapeProductUseCase | REQ-API-007, REQ-SCRAPE-010, REQ-PERSIST-002 |
| DeleteProductUseCase | REQ-API-006, REQ-REPO-003 |
| FetchProductsUseCase | REQ-API-002, REQ-FILTER-001 to REQ-FILTER-013, REQ-SCALE-002 |

All Use Cases are production-ready and follow SOLID principles, Domain-Driven Design patterns, and Laravel best practices.
