# Use Cases Quick Reference Card

## One-Liner Summary

7 production-ready Use Cases implementing complete CRUD + batch operations for product scraping and management.

---

## Quick Access

| Use Case | Primary Method | Main Purpose |
|----------|---------------|--------------|
| **CreateProductUseCase** | `execute(url, platform)` | Create product from URL |
| **UpdateProductUseCase** | `executeById(id)` | Re-scrape & update product |
| **BatchCreateProductsUseCase** | `execute(products[])` | Create up to 50 products |
| **ToggleWatchProductUseCase** | `execute(id, ?status)` | Toggle is_active status |
| **ScrapeProductUseCase** | `execute(id)` | Manual scrape trigger |
| **DeleteProductUseCase** | `execute(id)` | Delete product |
| **FetchProductsUseCase** | `execute(filters, page, perPage)` | Fetch with pagination |

---

## Quick Examples

### Create Product
```php
app(CreateProductUseCase::class)->execute($url, $platform);
```

### Update Product
```php
app(UpdateProductUseCase::class)->executeById($id);
app(UpdateProductUseCase::class)->executeByUrl($url, $platform);
app(UpdateProductUseCase::class)->executeByPlatformId($platformId, $platform);
```

### Batch Create (max 50)
```php
app(BatchCreateProductsUseCase::class)->execute([
    ['url' => '...', 'platform' => 'amazon'],
    ['url' => '...', 'platform' => 'jumia'],
]);
```

### Toggle Watch
```php
$toggle = app(ToggleWatchProductUseCase::class);
$toggle->activate($id);      // Enable watching
$toggle->deactivate($id);    // Disable watching
$toggle->execute($id);       // Toggle current status
$toggle->batchToggle([$ids], true); // Batch activate
```

### Manual Scrape
```php
$scrape = app(ScrapeProductUseCase::class);
$scrape->execute($id);                           // Single
$scrape->batchScrape([$ids]);                    // Multiple
$scrape->scrapeProductsNeedingUpdate($hours);    // Auto-update old ones
```

### Delete
```php
$delete = app(DeleteProductUseCase::class);
$delete->execute($id);              // Hard delete
$delete->softDelete($id);           // Soft delete (deactivate)
$delete->batchDelete([$ids]);       // Batch delete
```

### Fetch with Filters
```php
app(FetchProductsUseCase::class)->execute(
    filters: [
        'platform' => 'amazon',
        'min_price' => 50,
        'max_price' => 200,
        'min_rating' => 4.0,
        'search' => 'headphones',
        'category' => 'Electronics',
        'currency' => 'USD',
        'is_active' => true,
    ],
    page: 1,
    perPage: 20,
    sortBy: 'price',
    sortOrder: 'asc'
);
```

### Get Statistics
```php
app(FetchProductsUseCase::class)->getStatistics();
```

### Get Single Product
```php
app(FetchProductsUseCase::class)->getById($id);
```

---

## Response Format

All Use Cases return consistent array format:

```php
[
    'success' => true/false,
    'data' => [...],           // or 'product', 'results', etc.
    'message' => 'Success message',
    'error' => 'Error message', // if failed
    'error_code' => 'CODE',    // if failed
]
```

---

## Common Error Codes

| Code | Meaning |
|------|---------|
| `PRODUCT_NOT_FOUND` | Product doesn't exist |
| `PRODUCT_ALREADY_EXISTS` | Duplicate product |
| `INVALID_INPUT` | Validation failed |
| `BATCH_SIZE_EXCEEDED` | More than 50 products |
| `PRODUCT_INACTIVE` | Operation requires active product |
| `CREATION_FAILED` | Failed to create |
| `UPDATE_FAILED` | Failed to update |
| `SCRAPE_FAILED` | Scraping failed |
| `DELETION_FAILED` | Failed to delete |
| `FETCH_FAILED` | Failed to fetch |

---

## Available Filters (FetchProductsUseCase)

- `platform` (amazon/jumia)
- `min_price`, `max_price`
- `min_rating`, `max_rating`
- `search` (title search)
- `category` (platform_category)
- `currency` (price_currency)
- `platform_id`
- `is_active` (bool)
- `date_from`, `date_to`
- `last_scraped_from`, `last_scraped_to`
- `min_scrape_count`, `max_scrape_count`

---

## Sortable Fields (FetchProductsUseCase)

`id`, `title`, `price`, `rating`, `rating_count`, `platform`, `platform_category`, `created_at`, `updated_at`, `last_scraped_at`, `scrape_count`, `is_active`

---

## Batch Limits

- **BatchCreateProductsUseCase**: Max 50 products per batch
- **FetchProductsUseCase**: Max 100 items per page

---

## Controller Integration Pattern

```php
class ProductController extends Controller
{
    public function store(Request $request, CreateProductUseCase $useCase)
    {
        $validated = $request->validate([...]);
        $result = $useCase->execute($validated['url'], $validated['platform']);
        return response()->json($result, $result['success'] ? 201 : 422);
    }
}
```

---

## Testing Pattern

```php
public function test_use_case(): void
{
    $mock = $this->mock(DependencyInterface::class);
    $mock->shouldReceive('method')->andReturn($data);
    
    $useCase = new UseCase($mock);
    $result = $useCase->execute(...);
    
    $this->assertTrue($result['success']);
}
```

---

## Files

```
apps/api/app/UseCases/
├── CreateProductUseCase.php
├── UpdateProductUseCase.php
├── BatchCreateProductsUseCase.php
├── ToggleWatchProductUseCase.php
├── ScrapeProductUseCase.php
├── DeleteProductUseCase.php
└── FetchProductsUseCase.php

docs/
├── use-cases.md              # Full documentation (72KB)
└── use-cases-summary.md      # Implementation summary
```

---

## Full Documentation

See `docs/use-cases.md` for comprehensive documentation with detailed examples, parameters, return values, and integration guides.

