<?php

declare(strict_types=1);

namespace App\Providers;

use App\Facades\ProductMapper as ProductMapperFacade;
use App\Facades\ScrapingOrchestrator as ScrapingOrchestratorFacade;
use App\Facades\ScrapingService as ScrapingServiceFacade;
use App\Facades\ProxyService as ProxyServiceFacade;
use App\Facades\ProductRepository as ProductRepositoryFacade;
use App\Facades\ProductScrapingStorageService as ProductScrapingStorageServiceFacade;
use App\Services\ScrapingService;
use App\Services\ProductMapper;
use App\Services\ProxyService;
use App\Services\ScrapingOrchestrator;
use App\Services\ProductScrapingStorageService;
use App\Repositories\ProductRepository;
use Domain\Product\Service\ProductMapperInterface;
use Domain\Product\Service\ProxyServiceInterface;
use Domain\Product\Service\ScrapingOrchestratorInterface;
use Domain\Product\Service\ScrapingServiceInterface;
use Domain\Product\Service\ProductScrapingStorageServiceInterface;
use Domain\Product\Repository\ProductRepositoryInterface;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\ServiceProvider;

/**
 * Scraping Service Provider
 * 
 * Registers all scraping-related services and their dependencies.
 * Implements dependency injection for the scraping subsystem.
 */
class ScrapingServiceProvider extends ServiceProvider
{
    const PROXY_SERVICE = ProxyService::class;
    const SCRAPING_SERVICE = ScrapingService::class;
    const PRODUCT_MAPPER_SERVICE = ProductMapper::class;
    const SCRAPING_ORCHESTRATOR_SERVICE = ScrapingOrchestrator::class;
    const PRODUCT_REPOSITORY = ProductRepository::class;
    const PRODUCT_SCRAPING_STORAGE_SERVICE = ProductScrapingStorageService::class;

    /**
     * Register services
     */
    public function register(): void
    {
        // Register ProxyService as singleton
        $this->app->singleton(ProxyServiceInterface::class, function ($app) {
            return new (static::PROXY_SERVICE)(
                $app->make(HttpClient::class)
            );
        });

        // Register ScrapingService as singleton
        $this->app->singleton(ScrapingServiceInterface::class, function ($app) {
            return new (static::SCRAPING_SERVICE)(
                $app->make(ProxyServiceInterface::class),
                $app->make(HttpClient::class)
            );
        });

        // Register ProductMapper as singleton
        $this->app->singleton(ProductMapperInterface::class, function ($app) {
            return new (static::PRODUCT_MAPPER_SERVICE)();
        });

        // Register ScrapingOrchestrator as singleton
        $this->app->singleton(ScrapingOrchestratorInterface::class, function ($app) {
            return new (static::SCRAPING_ORCHESTRATOR_SERVICE)(
                $app->make(ScrapingServiceInterface::class),
                $app->make(ProductMapperInterface::class)
            );
        });

        // Register ProductRepository as singleton
        $this->app->singleton(ProductRepositoryInterface::class, function ($app) {
            return new (static::PRODUCT_REPOSITORY)(
                $app->make(\App\Models\Product::class)
            );
        });

        // Register ProductScrapingStorageService as singleton
        $this->app->singleton(ProductScrapingStorageServiceInterface::class, function ($app) {
            return new (static::PRODUCT_SCRAPING_STORAGE_SERVICE)();
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Register facades
        $loader = AliasLoader::getInstance();
        $loader->alias('ScrapingService', ScrapingServiceFacade::class);
        $loader->alias('ProductMapper', ProductMapperFacade::class);
        $loader->alias('ScrapingOrchestrator', ScrapingOrchestratorFacade::class);
        $loader->alias('ProxyService', ProxyServiceFacade::class);
        $loader->alias('ProductRepository', ProductRepositoryFacade::class);
        $loader->alias('ProductScrapingStorageService', ProductScrapingStorageServiceFacade::class);
    }
}
