# Domain Layer - Product Watching System

This directory contains the **framework-independent domain layer** for the Product Watching System. It implements Domain-Driven Design (DDD) principles with pure PHP classes containing only business logic.

## Directory Structure

```
domain/Product/
├── Entity/
│   └── Product.php                      # Core domain entity
├── ValueObject/
│   ├── Platform.php                     # Platform enum (amazon, jumia)
│   ├── Price.php                        # Price with validation
│   └── ProductUrl.php                   # Validated product URL
├── Repository/
│   └── ProductRepositoryInterface.php   # Data access contract
├── Service/
│   ├── ScrapingServiceInterface.php     # Scraping contract
│   ├── PlatformScraperInterface.php     # Platform-specific scraper
│   ├── ProxyServiceInterface.php        # Proxy management contract
│   ├── ScrapedProductData.php           # DTO for scraped data
│   ├── ProxyInfo.php                    # DTO for proxy info
│   └── ProxyServiceStatus.php           # DTO for proxy status
├── Event/
│   ├── DomainEvent.php                  # Base event
│   ├── ProductCreated.php               # Product creation event
│   ├── ProductScraped.php               # Scraping success event
│   ├── ProductPriceChanged.php          # Price change event
│   ├── ProductActivated.php             # Activation event
│   └── ProductDeactivated.php           # Deactivation event
└── Exception/
    ├── DomainException.php              # Base domain exception
    ├── InvalidPlatformException.php     # Invalid platform
    ├── InvalidPriceException.php        # Invalid price
    ├── InvalidProductUrlException.php   # Invalid URL
    ├── InvalidProductStateException.php # Business rule violations
    ├── ProductNotFoundException.php     # Product not found
    ├── ScrapingException.php            # Scraping failures
    └── UnsupportedPlatformException.php # Unsupported platform
```

## Requirements Implemented

### Architecture Requirements
- **REQ-ARCH-001**: Clean separation of concerns (domain, app, infra layers)
- **REQ-ARCH-002**: Domain layer contains abstract business logic
- **REQ-ARCH-003**: Domain includes entities and value types
- **REQ-ARCH-005**: Repository interfaces defined in domain
- **REQ-ARCH-006**: Service interfaces defined in domain
- **REQ-ARCH-014**: Custom exceptions for business rule violations

### Validation Requirements
- **REQ-VAL-001**: Price must be numeric and >= 0
- **REQ-VAL-002**: Product URL must be valid and match platform
- **REQ-VAL-003**: Title max 500 characters
- **REQ-VAL-004**: Product URL max 500 characters
- **REQ-VAL-005**: Image URL must be valid when provided
- **REQ-VAL-006**: Platform must be supported (amazon, jumia)

## Core Components

### Entity: Product

The `Product` entity represents a product being watched from e-commerce platforms.

**Key Features:**
- Immutable business rules enforcement
- Factory methods for creation and reconstitution
- Business logic methods (markAsScraped, activate, deactivate)
- Validation of business invariants

**Usage:**
```php
use Domain\Product\Entity\Product;
use Domain\Product\ValueObject\{Platform, Price, ProductUrl};

// Create new product
$product = Product::createNew(
    title: 'iPhone 15 Pro',
    price: Price::fromFloat(25999.00),
    productUrl: ProductUrl::fromString('https://amazon.eg/dp/B0EXAMPLE'),
    platform: Platform::amazon(),
    imageUrl: 'https://m.media-amazon.com/images/I/example.jpg'
);

// Mark as scraped
$product->markAsScraped();

// Update from scraping
$product->updateFromScraping(
    title: 'iPhone 15 Pro - Updated',
    price: Price::fromFloat(24999.00),
    imageUrl: 'https://m.media-amazon.com/images/I/example2.jpg'
);
```

### Value Objects

**Platform:**
```php
$platform = Platform::amazon();
$platform = Platform::jumia();
$platform = Platform::fromString('amazon');

if ($platform->isAmazon()) {
    // Amazon-specific logic
}

if ($platform->matchesUrl('https://amazon.eg/dp/B0EXAMPLE')) {
    // URL belongs to this platform
}
```

**Price:**
```php
$price = Price::fromFloat(25999.99);
$price = Price::fromString('25,999.99 EGP'); // Parses and validates

echo $price->toFormattedString('EGP'); // "25,999.99 EGP"

if ($price->isGreaterThan($otherPrice)) {
    // Price comparison
}

$difference = $newPrice->percentageDifferenceFrom($oldPrice);
```

**ProductUrl:**
```php
$url = ProductUrl::fromString('https://amazon.eg/dp/B0EXAMPLE');

if ($url->matchesPlatform(Platform::amazon())) {
    // URL matches platform
}

if ($url->isSecure()) {
    // Uses HTTPS
}

$normalized = $url->toNormalized(); // Removes tracking params
```

### Repository Interface

Defines data access operations:

```php
interface ProductRepositoryInterface
{
    public function findById(int $id): Product;
    public function findByUrl(ProductUrl $url, Platform $platform): Product;
    public function save(Product $product): Product;
    public function delete(Product $product): void;
    public function findAllActive(): array;
    public function findProductsNeedingScraping(int $maxHours = 24): array;
    // ... more methods
}
```

### Service Interfaces

**Scraping Service:**
```php
interface ScrapingServiceInterface
{
    public function scrapeProduct(ProductUrl $url, Platform $platform): ScrapedProductData;
    public function supportsPlatform(Platform $platform): bool;
    public function getScraperForPlatform(Platform $platform): PlatformScraperInterface;
}
```

**Proxy Service:**
```php
interface ProxyServiceInterface
{
    public function getNextProxy(): ?ProxyInfo;
    public function getAllProxies(): array;
    public function isHealthy(): bool;
    public function getStatus(): ProxyServiceStatus;
}
```

### Domain Events

Events represent significant business occurrences:

**ProductCreated:**
```php
$event = new ProductCreated($product);
// Event carries full product data
```

**ProductPriceChanged:**
```php
$event = new ProductPriceChanged($product, $oldPrice, $newPrice);
$change = $event->getPriceChange(); // Price difference
$percentage = $event->getPercentageChange(); // % change
```

**ProductScraped:**
```php
$event = new ProductScraped($product, dataChanged: true);
if ($event->hasDataChanged()) {
    // Product data was updated
}
```

### Domain Exceptions

All exceptions extend `DomainException` and use static factory methods:

```php
// Platform validation
throw InvalidPlatformException::unsupported('ebay', ['amazon', 'jumia']);

// Price validation
throw InvalidPriceException::tooLow(-10.00, 0.00);
throw InvalidPriceException::notNumeric('abc');

// URL validation
throw InvalidProductUrlException::empty();
throw InvalidProductUrlException::tooLong($url, 500);

// Business rule violations
throw InvalidProductStateException::cannotScrapeInactiveProduct($productId);
throw InvalidProductStateException::urlPlatformMismatch($url, $platform);

// Scraping failures
throw ScrapingException::timeout($url, 30);
throw ScrapingException::elementNotFound($url, '.price');
```

## Framework Independence

This domain layer is **completely independent** of Laravel or any framework:

- ✅ Pure PHP 8.1+ code
- ✅ No framework dependencies
- ✅ No database/ORM dependencies
- ✅ No HTTP/Request dependencies
- ✅ Only business logic and domain concepts
- ✅ Can be tested in isolation
- ✅ Reusable across different frameworks

## Business Rules Enforced

1. **Product Creation:**
   - Title cannot be empty, max 500 characters
   - Price must be >= 0 and < 1 billion
   - Product URL must be valid HTTPS/HTTP URL, max 500 chars
   - Product URL must match platform domain
   - Image URL must be valid when provided

2. **Product Scraping:**
   - Only active products can be scraped
   - Scrape count increments on each scrape
   - Last scraped timestamp updated
   - Products need scraping if never scraped or > 24 hours old

3. **Product State:**
   - Can activate/deactivate watching
   - Cannot scrape inactive products
   - State changes touch updated_at timestamp

4. **Platform Validation:**
   - Only amazon and jumia supported
   - URLs must match platform domains
   - Platform-specific scraper required

## Testing Strategy

Domain entities and value objects can be tested independently:

```php
// Test Product entity business rules
$product = Product::createNew(...);
$this->assertTrue($product->needsScraping());

// Test value object validation
$this->expectException(InvalidPriceException::class);
Price::fromFloat(-100);

// Test platform matching
$platform = Platform::amazon();
$this->assertTrue($platform->matchesUrl('https://amazon.eg/product'));
```

## Integration with Application Layer

The application layer (in `app/`) will provide concrete implementations:

- **Repository Implementation**: Eloquent-based ProductRepository
- **Service Implementations**: ScrapingService, ProxyService
- **Event Handlers**: Listen to domain events
- **Use Cases**: Orchestrate domain operations

## References

- **SRS Document**: Section 5.2.1 - Domain Layer
- **Requirements**: REQ-ARCH-001 through REQ-ARCH-015
- **Validation Rules**: REQ-VAL-001 through REQ-VAL-006
- **DDD Principles**: Entities, Value Objects, Aggregates, Domain Services
