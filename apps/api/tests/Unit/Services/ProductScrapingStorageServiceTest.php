<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ProductScrapingStorageService;
use Domain\Product\Service\ProductScrapingStorageServiceInterface;
use Domain\Product\Entity\Product;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\ProductUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Product Scraping Storage Service Test
 * 
 * Tests the ProductScrapingStorageService implementation to ensure it correctly
 * implements the domain ProductScrapingStorageServiceInterface.
 */
class ProductScrapingStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductScrapingStorageServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductScrapingStorageService();
    }

    /** @test */
    public function it_implements_the_domain_interface(): void
    {
        $this->assertInstanceOf(ProductScrapingStorageServiceInterface::class, $this->service);
    }

    /** @test */
    public function it_can_get_storage_statistics(): void
    {
        $stats = $this->service->getStorageStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_products', $stats);
        $this->assertArrayHasKey('active_products', $stats);
        $this->assertArrayHasKey('amazon_products', $stats);
        $this->assertArrayHasKey('jumia_products', $stats);
        $this->assertArrayHasKey('products_needing_scraping_24h', $stats);
        $this->assertArrayHasKey('products_needing_scraping_1h', $stats);

        // Initial state should be all zeros
        $this->assertEquals(0, $stats['total_products']);
        $this->assertEquals(0, $stats['active_products']);
        $this->assertEquals(0, $stats['amazon_products']);
        $this->assertEquals(0, $stats['jumia_products']);
    }

    /** @test */
    public function it_can_be_resolved_from_container(): void
    {
        $service = $this->app->make(ProductScrapingStorageServiceInterface::class);
        
        $this->assertInstanceOf(ProductScrapingStorageService::class, $service);
        $this->assertInstanceOf(ProductScrapingStorageServiceInterface::class, $service);
    }

    /** @test */
    public function it_returns_empty_results_for_products_needing_scraping_when_no_products_exist(): void
    {
        $results = $this->service->updateProductsNeedingScraping(24);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('failed', $results);
        $this->assertEmpty($results['success']);
        $this->assertEmpty($results['failed']);
    }

    /** @test */
    public function facade_can_be_resolved(): void
    {
        // Test that the facade can be resolved through the service provider
        $facade = app('ProductScrapingStorageService');
        
        $this->assertInstanceOf(ProductScrapingStorageService::class, $facade);
    }

    /** @test */
    public function service_can_be_accessed_via_facade_class(): void
    {
        // Test that the facade class resolves correctly
        $facade = \App\Facades\ProductScrapingStorageService::getFacadeRoot();
        
        $this->assertInstanceOf(ProductScrapingStorageService::class, $facade);
        
        // Test that facade methods can be called
        $stats = \App\Facades\ProductScrapingStorageService::getStorageStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_products', $stats);
    }
}