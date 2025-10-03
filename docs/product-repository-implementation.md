# ProductRepository Implementation Notes

## Overview

The `ProductRepository` has been successfully implemented under `apps/api/app/Repositories/ProductRepository.php`. It implements the domain `ProductRepositoryInterface` and provides full CRUD operations for products, utilizing the Laravel Eloquent `Product` model for database persistence.

## Key Features

### ✅ **Domain-Driven Design Implementation**
- Implements `Domain\Product\Repository\ProductRepositoryInterface`
- Maps between domain `Product` entities and Laravel `Product` models
- Maintains domain entity integrity and business rules

### ✅ **Complete CRUD Operations**
- `findById()` / `findByIdOrNull()`
- `findByUrl()` / `findByUrlOrNull()`
- `save()` - handles both create and update
- `delete()`
- Various finder methods with filters

### ✅ **Business Logic Support**
- Find products needing scraping based on time criteria
- Count operations for statistics
- Platform-specific queries
- Active/inactive product filtering

### ✅ **Integration Ready**
- Service provider binding configured
- Facade available for easy access
- Comprehensive logging for debugging
- Error handling with domain exceptions

## Usage Examples

### Basic Repository Operations

```php
use App\Facades\ProductRepository;
use Domain\Product\Entity\Product;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\Price;
use Domain\Product\ValueObject\ProductUrl;

// Create a new product
$product = Product::createNew(
    title: 'Sony WH-1000XM4 Headphones',
    price: Price::fromFloat(349.99),
    productUrl: ProductUrl::fromString('https://amazon.com/dp/B0863TXGM3'),
    platform: Platform::fromString('amazon'),
    priceCurrency: 'USD',
    rating: 4.7,
    ratingCount: 15420
);

// Save to database
$savedProduct = ProductRepository::save($product);
echo "Created product with ID: " . $savedProduct->getId();

// Find by ID
$foundProduct = ProductRepository::findById($savedProduct->getId());

// Find by URL and platform
$product = ProductRepository::findByUrl(
    ProductUrl::fromString('https://amazon.com/dp/B0863TXGM3'),
    Platform::fromString('amazon')
);

// Update product
$product->updateFromScraping(
    title: 'Sony WH-1000XM4 Wireless Noise Canceling Headphones',
    price: Price::fromFloat(299.99), // Price dropped!
    rating: 4.8,
    ratingCount: 16500
);
$product->markAsScraped();
ProductRepository::save($product);

// Delete product
ProductRepository::delete($product);
```

### Integration with ScrapingOrchestrator

```php
use App\Services\ProductScrapingStorageService;
use Domain\Product\ValueObject\ProductUrl;
use Domain\Product\ValueObject\Platform;

// Create the integrated service
$scrapingStorage = new ProductScrapingStorageService();

// Scrape a product and automatically store/update it
$url = ProductUrl::fromString('https://amazon.com/dp/B08N5WRWNW');
$platform = Platform::fromString('amazon');

$product = $scrapingStorage->scrapeAndStore($url, $platform);
echo "Stored product: " . $product->getTitle() . " - $" . $product->getPrice()->toFloat();

// Batch scraping and storage
$urlPlatformPairs = [
    ['url' => ProductUrl::fromString('https://amazon.com/dp/B123'), 'platform' => Platform::fromString('amazon')],
    ['url' => ProductUrl::fromString('https://jumia.com/product-456'), 'platform' => Platform::fromString('jumia')],
];

$results = $scrapingStorage->scrapeAndStoreMultiple($urlPlatformPairs);
echo "Success: " . count($results['success']) . ", Failed: " . count($results['failed']);

// Update products that need re-scraping
$updateResults = $scrapingStorage->updateProductsNeedingScraping(24); // 24 hours
```

### Query Operations

```php
// Find all active products
$activeProducts = ProductRepository::findAllActive();

// Find products by platform
$amazonProducts = ProductRepository::findActiveByPlatform(Platform::fromString('amazon'));
$jumiaProducts = ProductRepository::findActiveByPlatform(Platform::fromString('jumia'));

// Find products needing scraping
$needsScraping = ProductRepository::findProductsNeedingScraping(24); // Last 24 hours

// Statistics
$totalCount = ProductRepository::count();
$activeCount = ProductRepository::countActive();
$amazonCount = ProductRepository::countByPlatform(Platform::fromString('amazon'));

// Check existence
$exists = ProductRepository::existsByUrl(
    ProductUrl::fromString('https://amazon.com/dp/B123'),
    Platform::fromString('amazon')
);
```

### Using the Facade

```php
// The ProductRepository facade is automatically registered
use ProductRepository; // Available globally

$product = ProductRepository::findById(1);
$products = ProductRepository::findAllActive();
```

## Database Schema Support

The repository works with the existing `products` table schema:

```sql
- id (primary key)
- title (string, max 500 chars)
- price (decimal)
- price_currency (string, 3 chars, ISO 4217)
- rating (decimal, 0-5, nullable)
- rating_count (integer, nullable)
- image_url (string, nullable)
- product_url (string, max 2048 chars)
- platform (enum: amazon, jumia)
- platform_category (string, nullable)
- last_scraped_at (timestamp, nullable)
- scrape_count (integer, default 0)
- is_active (boolean, default true)
- created_at (timestamp)
- updated_at (timestamp)
```

## Error Handling

The repository properly handles domain exceptions:

```php
use Domain\Product\Exception\ProductNotFoundException;

try {
    $product = ProductRepository::findById(999);
} catch (ProductNotFoundException $e) {
    echo "Product not found: " . $e->getMessage();
}

// Or use the null-safe versions
$product = ProductRepository::findByIdOrNull(999); // Returns null instead of throwing
```

## Service Provider Configuration

The repository is automatically registered in `ScrapingServiceProvider`:

```php
// In ScrapingServiceProvider::register()
$this->app->singleton(ProductRepositoryInterface::class, function ($app) {
    return new ProductRepository($app->make(\App\Models\Product::class));
});
```

## Testing

Comprehensive unit tests are available in `tests/Unit/Repositories/ProductRepositoryTest.php`:

```bash
# Run repository tests
php artisan test tests/Unit/Repositories/ProductRepositoryTest.php
```

## Integration with Existing System

The ProductRepository seamlessly integrates with:

1. **Domain Layer**: Implements domain contracts and maintains business rules
2. **Scraping Services**: Can store results from any scraping operation
3. **API Layer**: Provides data for REST API endpoints
4. **Laravel Ecosystem**: Uses Eloquent, facades, service providers

## Performance Considerations

- Repository uses singleton pattern for efficient memory usage
- Database queries are optimized with appropriate indexes
- Bulk operations available for batch processing
- Lazy loading supported for related data
