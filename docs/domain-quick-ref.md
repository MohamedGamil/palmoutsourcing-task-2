# Domain Layer - Quick Reference Guide

## Import Statements

```php
// Entity
use Domain\Product\Entity\Product;

// Value Objects
use Domain\Product\ValueObject\{Platform, Price, ProductUrl};

// Repository
use Domain\Product\Repository\ProductRepositoryInterface;

// Services
use Domain\Product\Service\{
    ScrapingServiceInterface,
    PlatformScraperInterface,
    ProxyServiceInterface,
    ScrapedProductData,
    ProxyInfo,
    ProxyServiceStatus
};

// Events
use Domain\Product\Event\{
    ProductCreated,
    ProductScraped,
    ProductPriceChanged,
    ProductActivated,
    ProductDeactivated
};

// Exceptions
use Domain\Product\Exception\{
    InvalidPlatformException,
    InvalidPriceException,
    InvalidProductUrlException,
    InvalidProductStateException,
    ProductNotFoundException,
    ScrapingException,
    UnsupportedPlatformException
};
```

---

## Creating Domain Objects

### Product Entity

```php
// Create new product
$product = Product::createNew(
    title: 'iPhone 15 Pro',
    price: Price::fromFloat(25999.00),
    productUrl: ProductUrl::fromString('https://amazon.eg/dp/B0EXAMPLE'),
    platform: Platform::amazon(),
    imageUrl: 'https://m.media-amazon.com/images/I/example.jpg'
);

// Reconstitute from database (for repository)
$product = Product::reconstitute(
    id: 1,
    title: 'iPhone 15 Pro',
    price: Price::fromFloat(25999.00),
    productUrl: ProductUrl::fromString('https://amazon.eg/dp/B0EXAMPLE'),
    platform: Platform::amazon(),
    imageUrl: 'https://m.media-amazon.com/images/I/example.jpg',
    lastScrapedAt: new DateTime('2025-10-03 14:00:00'),
    scrapeCount: 5,
    isActive: true,
    createdAt: new DateTime('2025-10-01'),
    updatedAt: new DateTime('2025-10-03')
);
```

### Value Objects

```php
// Platform
$amazon = Platform::amazon();
$jumia = Platform::jumia();
$platform = Platform::fromString('amazon');

// Price
$price = Price::fromFloat(25999.99);
$price = Price::fromString('25,999.99');
$zero = Price::zero();

// ProductUrl
$url = ProductUrl::fromString('https://amazon.eg/dp/B0EXAMPLE');
```

### Events

```php
// Product created
$event = new ProductCreated($product);

// Product scraped
$event = new ProductScraped($product, dataChanged: true);

// Price changed
$event = new ProductPriceChanged($product, $oldPrice, $newPrice);

// Product activated/deactivated
$event = new ProductActivated($product);
$event = new ProductDeactivated($product);
```

---

## Common Operations

### Product Operations

```php
// Mark as scraped
$product->markAsScraped();

// Update from scraping
$product->updateFromScraping(
    title: 'Updated Title',
    price: Price::fromFloat(24999.00),
    imageUrl: 'https://...'
);

// Activate/deactivate
$product->activate();
$product->deactivate();

// Check if needs scraping
if ($product->needsScraping(maxHoursSinceLastScrape: 24)) {
    // Scrape the product
}

// Get data
$id = $product->getId();
$title = $product->getTitle();
$price = $product->getPrice();
$url = $product->getProductUrl();
$platform = $product->getPlatform();
$isActive = $product->isActive();
$scrapeCount = $product->getScrapeCount();
$lastScraped = $product->getLastScrapedAt();
$isNew = $product->isNew();

// Serialize
$array = $product->toArray();
```

### Platform Operations

```php
// Check platform type
if ($platform->isAmazon()) {
    // Amazon logic
}

if ($platform->isJumia()) {
    // Jumia logic
}

// URL matching
if ($platform->matchesUrl('https://amazon.eg/product')) {
    // URL belongs to this platform
}

// Get domains
$domains = $platform->getDomains();
// ['amazon.com', 'amazon.co.uk', 'amazon.eg', ...]

// Compare
if ($platform->equals($otherPlatform)) {
    // Same platform
}

// To string
echo $platform->toString(); // "amazon"
echo $platform;             // "amazon"
```

### Price Operations

```php
// Get values
$float = $price->toFloat();          // 25999.99
$string = $price->toString();        // "25999.99"
$formatted = $price->toFormattedString('EGP'); // "25,999.99 EGP"

// Comparisons
if ($price->isGreaterThan($otherPrice)) { }
if ($price->isLessThan($otherPrice)) { }
if ($price->equals($otherPrice)) { }
if ($price->isZero()) { }

// Calculations
$sum = $price->add($otherPrice);
$difference = $price->subtract($otherPrice);
$increased = $price->multiply(1.15); // 15% increase

// Percentage change
$percentChange = $newPrice->percentageDifferenceFrom($oldPrice);
// Returns: 10.5 (for 10.5% increase)
```

### ProductUrl Operations

```php
// Validate against platform
if ($url->matchesPlatform($platform)) {
    // URL is valid for this platform
}

// Get parts
$domain = $url->getDomain();    // "amazon.eg"
$scheme = $url->getScheme();    // "https"

// Check security
if ($url->isSecure()) {
    // Uses HTTPS
}

// Normalize (remove tracking)
$normalized = $url->toNormalized();

// Compare
if ($url->equals($otherUrl)) {
    // Same URL
}

// To string
echo $url->toString();
echo $url;
```

---

## Event Handling Patterns

### Dispatch Events (in Application Layer)

```php
// After creating product
event(new ProductCreated($product));

// After scraping
$dataChanged = !$oldPrice->equals($newPrice);
event(new ProductScraped($product, $dataChanged));

// After price change
if (!$oldPrice->equals($newPrice)) {
    event(new ProductPriceChanged($product, $oldPrice, $newPrice));
}

// After activation
event(new ProductActivated($product));
```

### Listen to Events

```php
// In EventServiceProvider
protected $listen = [
    ProductCreated::class => [
        SendWelcomeNotification::class,
        LogProductCreated::class,
    ],
    ProductPriceChanged::class => [
        SendPriceAlertNotification::class,
        LogPriceChange::class,
    ],
];
```

### Event Data Access

```php
// ProductCreated event
$product = $event->getProduct();
$occurred = $event->occurredAt();
$array = $event->toArray();

// ProductPriceChanged event
$product = $event->getProduct();
$oldPrice = $event->getOldPrice();
$newPrice = $event->getNewPrice();
$change = $event->getPriceChange();
$percent = $event->getPercentageChange();
$isIncrease = $event->isPriceIncrease();
$isDecrease = $event->isPriceDecrease();
```

---

## Exception Handling

### Throw Exceptions

```php
// Platform validation
if (!in_array($platform, ['amazon', 'jumia'])) {
    throw InvalidPlatformException::unsupported($platform, ['amazon', 'jumia']);
}

// Price validation
if ($amount < 0) {
    throw InvalidPriceException::tooLow($amount, 0.00);
}

if (!is_numeric($value)) {
    throw InvalidPriceException::notNumeric($value);
}

// URL validation
if (empty($url)) {
    throw InvalidProductUrlException::empty();
}

if (mb_strlen($url) > 500) {
    throw InvalidProductUrlException::tooLong($url, 500);
}

// Business rules
if (!$product->isActive()) {
    throw InvalidProductStateException::cannotScrapeInactiveProduct($product->getId());
}

if (!$url->matchesPlatform($platform)) {
    throw InvalidProductStateException::urlPlatformMismatch($url->toString(), $platform->toString());
}

// Not found
throw ProductNotFoundException::byId($id);
throw ProductNotFoundException::byUrl($url);

// Scraping failures
throw ScrapingException::timeout($url, 30);
throw ScrapingException::elementNotFound($url, '.price-element');
throw ScrapingException::proxyUnavailable();
```

### Catch Exceptions

```php
try {
    $product = Product::createNew(...);
} catch (InvalidPriceException $e) {
    // Handle invalid price
} catch (InvalidProductUrlException $e) {
    // Handle invalid URL
} catch (InvalidProductStateException $e) {
    // Handle business rule violation
}

try {
    $product = $repository->findById($id);
} catch (ProductNotFoundException $e) {
    // Handle not found
}

try {
    $data = $scrapingService->scrapeProduct($url, $platform);
} catch (ScrapingException $e) {
    // Handle scraping failure
} catch (UnsupportedPlatformException $e) {
    // Handle unsupported platform
}
```

---

## Repository Pattern (Application Layer)

### Interface Contract

```php
interface ProductRepositoryInterface
{
    public function findById(int $id): Product;
    public function findByIdOrNull(int $id): ?Product;
    public function findByUrl(ProductUrl $url, Platform $platform): Product;
    public function findByUrlOrNull(ProductUrl $url, Platform $platform): ?Product;
    public function save(Product $product): Product;
    public function delete(Product $product): void;
    public function findAllActive(): array;
    public function findActiveByPlatform(Platform $platform): array;
    public function findProductsNeedingScraping(int $maxHours = 24): array;
    public function count(): int;
    public function countActive(): int;
    public function countByPlatform(Platform $platform): int;
    public function existsByUrl(ProductUrl $url, Platform $platform): bool;
}
```

### Usage Example

```php
class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}

    public function createProduct(array $data): Product
    {
        $product = Product::createNew(
            title: $data['title'],
            price: Price::fromFloat($data['price']),
            productUrl: ProductUrl::fromString($data['url']),
            platform: Platform::fromString($data['platform']),
            imageUrl: $data['image_url'] ?? null
        );

        $saved = $this->repository->save($product);
        
        event(new ProductCreated($saved));
        
        return $saved;
    }

    public function getActiveProducts(): array
    {
        return $this->repository->findAllActive();
    }

    public function getProductsNeedingScraping(): array
    {
        return $this->repository->findProductsNeedingScraping(24);
    }
}
```

---

## Scraping Service Pattern

### Interface Contract

```php
interface ScrapingServiceInterface
{
    public function scrapeProduct(ProductUrl $url, Platform $platform): ScrapedProductData;
    public function supportsPlatform(Platform $platform): bool;
    public function getScraperForPlatform(Platform $platform): PlatformScraperInterface;
}
```

### Usage Example

```php
class ScrapeProductUseCase
{
    public function __construct(
        private ScrapingServiceInterface $scrapingService,
        private ProductRepositoryInterface $repository
    ) {}

    public function execute(int $productId): void
    {
        $product = $this->repository->findById($productId);

        if (!$product->isActive()) {
            throw InvalidProductStateException::cannotScrapeInactiveProduct($productId);
        }

        $oldPrice = $product->getPrice();

        $scrapedData = $this->scrapingService->scrapeProduct(
            $product->getProductUrl(),
            $product->getPlatform()
        );

        $product->updateFromScraping(
            title: $scrapedData->getTitle(),
            price: $scrapedData->getPrice(),
            imageUrl: $scrapedData->getImageUrl()
        );

        $product->markAsScraped();

        $this->repository->save($product);

        event(new ProductScraped($product, dataChanged: true));

        if (!$oldPrice->equals($scrapedData->getPrice())) {
            event(new ProductPriceChanged($product, $oldPrice, $scrapedData->getPrice()));
        }
    }
}
```

---

## Proxy Service Pattern

### Interface Contract

```php
interface ProxyServiceInterface
{
    public function getNextProxy(): ?ProxyInfo;
    public function getAllProxies(): array;
    public function isHealthy(): bool;
    public function getStatus(): ProxyServiceStatus;
}
```

### Usage Example

```php
class HttpScrapingService
{
    public function __construct(
        private ProxyServiceInterface $proxyService,
        private HttpClient $httpClient
    ) {}

    public function scrapeWithProxy(ProductUrl $url): string
    {
        $proxy = $this->proxyService->getNextProxy();

        if ($proxy === null) {
            throw ScrapingException::proxyUnavailable();
        }

        $response = $this->httpClient->get($url->toString(), [
            'proxy' => $proxy->getUrl(),
            'timeout' => 30,
        ]);

        return $response->body();
    }
}
```

---

## Data Transfer Objects

### ScrapedProductData

```php
$data = new ScrapedProductData(
    title: 'Product Title',
    price: Price::fromFloat(999.99),
    imageUrl: 'https://...'
);

$title = $data->getTitle();
$price = $data->getPrice();
$imageUrl = $data->getImageUrl();
$array = $data->toArray();
```

### ProxyInfo

```php
$proxy = new ProxyInfo(
    host: '167.99.112.184',
    port: 8080,
    isHealthy: true,
    lastChecked: '2025-10-03 14:30:00'
);

$host = $proxy->getHost();
$port = $proxy->getPort();
$isHealthy = $proxy->isHealthy();
$url = $proxy->getUrl(); // "http://167.99.112.184:8080"
$array = $proxy->toArray();
```

### ProxyServiceStatus

```php
$status = new ProxyServiceStatus(
    totalProxies: 10,
    healthyProxies: 8,
    isHealthy: true,
    message: 'Service operational'
);

$total = $status->getTotalProxies();
$healthy = $status->getHealthyProxies();
$isHealthy = $status->isHealthy();
$message = $status->getMessage();
$array = $status->toArray();
```

---

## Testing Examples

### Testing Product Entity

```php
class ProductTest extends TestCase
{
    public function test_can_create_new_product(): void
    {
        $product = Product::createNew(
            title: 'Test Product',
            price: Price::fromFloat(100.00),
            productUrl: ProductUrl::fromString('https://amazon.eg/test'),
            platform: Platform::amazon()
        );

        $this->assertTrue($product->isNew());
        $this->assertTrue($product->isActive());
        $this->assertEquals(0, $product->getScrapeCount());
    }

    public function test_mark_as_scraped_increments_count(): void
    {
        $product = Product::createNew(...);
        
        $product->markAsScraped();
        
        $this->assertEquals(1, $product->getScrapeCount());
        $this->assertNotNull($product->getLastScrapedAt());
    }

    public function test_cannot_scrape_inactive_product(): void
    {
        $product = Product::createNew(...);
        $product->deactivate();

        $this->expectException(InvalidProductStateException::class);
        $product->markAsScraped();
    }
}
```

### Testing Value Objects

```php
class PriceTest extends TestCase
{
    public function test_cannot_create_negative_price(): void
    {
        $this->expectException(InvalidPriceException::class);
        Price::fromFloat(-100);
    }

    public function test_price_calculations(): void
    {
        $price1 = Price::fromFloat(100);
        $price2 = Price::fromFloat(150);

        $sum = $price1->add($price2);
        $this->assertEquals(250, $sum->toFloat());

        $diff = $price2->subtract($price1);
        $this->assertEquals(50, $diff->toFloat());

        $this->assertTrue($price2->isGreaterThan($price1));
        $this->assertTrue($price1->isLessThan($price2));
    }

    public function test_percentage_change_calculation(): void
    {
        $oldPrice = Price::fromFloat(100);
        $newPrice = Price::fromFloat(110);

        $change = $newPrice->percentageDifferenceFrom($oldPrice);
        
        $this->assertEquals(10.0, $change);
    }
}
```

---

## Complete Example: Create and Scrape Product

```php
// 1. Create product
$product = Product::createNew(
    title: 'iPhone 15 Pro',
    price: Price::fromFloat(25999.00),
    productUrl: ProductUrl::fromString('https://amazon.eg/dp/B0EXAMPLE'),
    platform: Platform::amazon(),
    imageUrl: 'https://m.media-amazon.com/images/I/example.jpg'
);

// 2. Save to repository
$saved = $repository->save($product);

// 3. Fire event
event(new ProductCreated($saved));

// 4. Later: scrape the product
if ($saved->needsScraping(24)) {
    $oldPrice = $saved->getPrice();

    $scrapedData = $scrapingService->scrapeProduct(
        $saved->getProductUrl(),
        $saved->getPlatform()
    );

    $saved->updateFromScraping(
        title: $scrapedData->getTitle(),
        price: $scrapedData->getPrice(),
        imageUrl: $scrapedData->getImageUrl()
    );

    $saved->markAsScraped();
    
    $repository->save($saved);

    event(new ProductScraped($saved, dataChanged: true));

    if (!$oldPrice->equals($scrapedData->getPrice())) {
        event(new ProductPriceChanged($saved, $oldPrice, $scrapedData->getPrice()));
    }
}
```

---

## Best Practices

1. **Always use value objects** instead of primitives for domain concepts
2. **Validate at the domain boundary** - let exceptions bubble up
3. **Use factory methods** for clear object creation intent
4. **Dispatch events** after successful domain operations
5. **Keep domain pure** - no framework dependencies
6. **Use repository interfaces** for data access abstraction
7. **Implement use cases** in application layer to orchestrate domain operations
8. **Test domain logic** independently from infrastructure
