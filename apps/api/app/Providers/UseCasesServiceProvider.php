<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ProductCacheService;
use App\UseCases\BatchCreateProductsUseCase;
use App\UseCases\CreateProductUseCase;
use App\UseCases\DeleteProductUseCase;
use App\UseCases\FetchProductsUseCase;
use App\UseCases\ScrapeProductUseCase;
use App\UseCases\ToggleWatchProductUseCase;
use App\UseCases\UpdateProductUseCase;
use Domain\Product\Service\ProductScrapingStorageServiceInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Use Cases Service Provider
 * 
 * Registers all application Use Cases with the service container.
 * Use Cases implement application-specific business logic and orchestrate
 * domain entities, services, and repositories.
 * 
 * Requirements Implemented:
 * - REQ-ARCH-007: App layer implements use-cases for application logic
 * - REQ-ARCH-015: Service providers configure app layer dependencies
 * 
 * @package App\Providers
 */
class UseCasesServiceProvider extends ServiceProvider
{
    /**
     * Register Use Cases with the service container
     * 
     * All Use Cases are registered as singletons for better performance
     * since they don't maintain state between requests.
     */
    public function register(): void
    {
        // ProductCacheService - Centralized caching for product operations
        $this->app->singleton(ProductCacheService::class, function ($app) {
            return new ProductCacheService();
        });

        // CreateProductUseCase - Create product from URL
        $this->app->singleton(CreateProductUseCase::class, function ($app) {
            return new CreateProductUseCase(
                $app->make(ProductScrapingStorageServiceInterface::class)
            );
        });

        // UpdateProductUseCase - Re-scrape and update product
        $this->app->singleton(UpdateProductUseCase::class, function ($app) {
            return new UpdateProductUseCase(
                $app->make(ProductScrapingStorageServiceInterface::class)
            );
        });

        // BatchCreateProductsUseCase - Batch create up to 50 products
        $this->app->singleton(BatchCreateProductsUseCase::class, function ($app) {
            return new BatchCreateProductsUseCase(
                $app->make(CreateProductUseCase::class)
            );
        });

        // ToggleWatchProductUseCase - Toggle product watch status
        $this->app->singleton(ToggleWatchProductUseCase::class, function ($app) {
            return new ToggleWatchProductUseCase();
        });

        // ScrapeProductUseCase - Manual scrape trigger
        $this->app->singleton(ScrapeProductUseCase::class, function ($app) {
            return new ScrapeProductUseCase(
                $app->make(ProductScrapingStorageServiceInterface::class)
            );
        });

        // DeleteProductUseCase - Delete products from database
        $this->app->singleton(DeleteProductUseCase::class, function ($app) {
            return new DeleteProductUseCase();
        });

        // FetchProductsUseCase - Fetch products with filtering and pagination
        $this->app->singleton(FetchProductsUseCase::class, function ($app) {
            return new FetchProductsUseCase(
                $app->make(ProductCacheService::class)
            );
        });
    }

    /**
     * Bootstrap any application services
     */
    public function boot(): void
    {
        // No bootstrapping needed for Use Cases
    }

    /**
     * Get the services provided by the provider
     * 
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            ProductCacheService::class,
            CreateProductUseCase::class,
            UpdateProductUseCase::class,
            BatchCreateProductsUseCase::class,
            ToggleWatchProductUseCase::class,
            ScrapeProductUseCase::class,
            DeleteProductUseCase::class,
            FetchProductsUseCase::class,
        ];
    }
}
