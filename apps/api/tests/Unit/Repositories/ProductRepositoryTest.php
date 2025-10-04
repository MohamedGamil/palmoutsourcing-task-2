<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\ProductRepository;
use App\Models\Product as ProductModel;
use App\Services\ProductCacheService;
use Domain\Product\Entity\Product;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\Price;
use Domain\Product\ValueObject\ProductUrl;
use Domain\Product\Exception\ProductNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

/**
 * Product Repository Test
 * 
 * Tests the ProductRepository implementation to ensure it correctly
 * implements the domain ProductRepositoryInterface.
 */
class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Cache to prevent actual caching during tests
        Cache::spy();
        
        // Create repository with required dependencies
        $this->repository = new ProductRepository(
            new ProductModel(),
            new ProductCacheService()
        );
    }

    /** @test */
    public function it_can_create_and_find_a_product(): void
    {
        // Create a domain product
        $product = Product::createNew(
            title: 'Test Product',
            price: Price::fromFloat(99.99),
            productUrl: ProductUrl::fromString('https://amazon.com/dp/TEST123'),
            platform: Platform::fromString('amazon'),
            priceCurrency: 'USD',
            rating: 4.5,
            ratingCount: 100
        );

        // Save it
        $savedProduct = $this->repository->save($product);

        // Verify it was saved
        $this->assertNotNull($savedProduct->getId());
        $this->assertEquals('Test Product', $savedProduct->getTitle());
        $this->assertEquals(99.99, $savedProduct->getPrice()->toFloat());

        // Find by ID
        $foundProduct = $this->repository->findById($savedProduct->getId());
        $this->assertEquals($savedProduct->getId(), $foundProduct->getId());
        $this->assertEquals('Test Product', $foundProduct->getTitle());
    }

    /** @test */
    public function it_can_find_product_by_url_and_platform(): void
    {
        // Create a domain product
        $product = Product::createNew(
            title: 'Amazon Product',
            price: Price::fromFloat(149.99),
            productUrl: ProductUrl::fromString('https://amazon.com/dp/AMAZON123'),
            platform: Platform::fromString('amazon')
        );

        $savedProduct = $this->repository->save($product);

        // Find by URL and platform
        $foundProduct = $this->repository->findByUrl(
            ProductUrl::fromString('https://amazon.com/dp/AMAZON123'),
            Platform::fromString('amazon')
        );

        $this->assertEquals($savedProduct->getId(), $foundProduct->getId());
        $this->assertEquals('Amazon Product', $foundProduct->getTitle());
    }

    /** @test */
    public function it_throws_exception_when_product_not_found_by_id(): void
    {
        $this->expectException(ProductNotFoundException::class);
        $this->repository->findById(999);
    }

    /** @test */
    public function it_returns_null_when_product_not_found_by_id_or_null(): void
    {
        $result = $this->repository->findByIdOrNull(999);
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_update_existing_product(): void
    {
        // Create and save product
        $product = Product::createNew(
            title: 'Original Title',
            price: Price::fromFloat(50.00),
            productUrl: ProductUrl::fromString('https://jumia.com/test-product'),
            platform: Platform::fromString('jumia')
        );

        $savedProduct = $this->repository->save($product);
        $originalId = $savedProduct->getId();

        // Update the domain product
        $savedProduct->updateFromScraping(
            title: 'Updated Title',
            price: Price::fromFloat(45.00),
            rating: 4.0,
            ratingCount: 50
        );

        // Save again
        $updatedProduct = $this->repository->save($savedProduct);

        // Verify update
        $this->assertEquals($originalId, $updatedProduct->getId());
        $this->assertEquals('Updated Title', $updatedProduct->getTitle());
        $this->assertEquals(45.00, $updatedProduct->getPrice()->toFloat());
        $this->assertEquals(4.0, $updatedProduct->getRating());
    }

    /** @test */
    public function it_can_find_active_products(): void
    {
        // Create active product
        $activeProduct = Product::createNew(
            title: 'Active Product',
            price: Price::fromFloat(100.00),
            productUrl: ProductUrl::fromString('https://amazon.com/active'),
            platform: Platform::fromString('amazon')
        );

        // Create inactive product
        $inactiveProduct = Product::createNew(
            title: 'Inactive Product',
            price: Price::fromFloat(200.00),
            productUrl: ProductUrl::fromString('https://amazon.com/inactive'),
            platform: Platform::fromString('amazon')
        );
        $inactiveProduct->deactivate();

        $this->repository->save($activeProduct);
        $this->repository->save($inactiveProduct);

        $activeProducts = $this->repository->findAllActive();

        $this->assertCount(1, $activeProducts);
        $this->assertEquals('Active Product', $activeProducts[0]->getTitle());
    }

    /** @test */
    public function it_can_find_products_by_platform(): void
    {
        // Create Amazon product
        $amazonProduct = Product::createNew(
            title: 'Amazon Product',
            price: Price::fromFloat(100.00),
            productUrl: ProductUrl::fromString('https://amazon.com/product1'),
            platform: Platform::fromString('amazon')
        );

        // Create Jumia product
        $jumiaProduct = Product::createNew(
            title: 'Jumia Product',
            price: Price::fromFloat(150.00),
            productUrl: ProductUrl::fromString('https://jumia.com/product1'),
            platform: Platform::fromString('jumia')
        );

        $this->repository->save($amazonProduct);
        $this->repository->save($jumiaProduct);

        $amazonProducts = $this->repository->findActiveByPlatform(Platform::fromString('amazon'));
        $jumiaProducts = $this->repository->findActiveByPlatform(Platform::fromString('jumia'));

        $this->assertCount(1, $amazonProducts);
        $this->assertCount(1, $jumiaProducts);
        $this->assertEquals('Amazon Product', $amazonProducts[0]->getTitle());
        $this->assertEquals('Jumia Product', $jumiaProducts[0]->getTitle());
    }

    /** @test */
    public function it_can_check_if_product_exists_by_url(): void
    {
        $product = Product::createNew(
            title: 'Test Product',
            price: Price::fromFloat(99.99),
            productUrl: ProductUrl::fromString('https://amazon.com/exists'),
            platform: Platform::fromString('amazon')
        );

        $this->repository->save($product);

        $exists = $this->repository->existsByUrl(
            ProductUrl::fromString('https://amazon.com/exists'),
            Platform::fromString('amazon')
        );

        $notExists = $this->repository->existsByUrl(
            ProductUrl::fromString('https://amazon.com/not-exists'),
            Platform::fromString('amazon')
        );

        $this->assertTrue($exists);
        $this->assertFalse($notExists);
    }

    /** @test */
    public function it_can_count_products(): void
    {
        // Create some products
        for ($i = 1; $i <= 3; $i++) {
            $product = Product::createNew(
                title: "Product {$i}",
                price: Price::fromFloat(100.00 * $i),
                productUrl: ProductUrl::fromString("https://amazon.com/product{$i}"),
                platform: Platform::fromString('amazon')
            );
            $this->repository->save($product);
        }

        $this->assertEquals(3, $this->repository->count());
        $this->assertEquals(3, $this->repository->countActive());
        $this->assertEquals(3, $this->repository->countByPlatform(Platform::fromString('amazon')));
        $this->assertEquals(0, $this->repository->countByPlatform(Platform::fromString('jumia')));
    }

    /** @test */
    public function it_can_delete_product(): void
    {
        $product = Product::createNew(
            title: 'To Be Deleted',
            price: Price::fromFloat(99.99),
            productUrl: ProductUrl::fromString('https://amazon.com/delete-me'),
            platform: Platform::fromString('amazon')
        );

        $savedProduct = $this->repository->save($product);
        $productId = $savedProduct->getId();

        // Verify it exists
        $this->assertNotNull($this->repository->findByIdOrNull($productId));

        // Delete it
        $this->repository->delete($savedProduct);

        // Verify it's gone
        $this->assertNull($this->repository->findByIdOrNull($productId));
    }
}