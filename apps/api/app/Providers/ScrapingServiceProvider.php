<?php

declare(strict_types=1);

namespace App\Providers;

use App\Facades\ProductMapper as ProductMapperFacade;
use App\Facades\ScrapingOrchestrator as ScrapingOrchestratorFacade;
use App\Facades\ScrapingService as ScrapingServiceFacade;
use App\Facades\ProxyService as ProxyServiceFacade;
use App\Services\GuzzleScrapper;
use App\Services\ProductMapper;
use App\Services\ProxyService;
use App\Services\ScrapingOrchestrator;
use Domain\Product\Service\ProductMapperInterface;
use Domain\Product\Service\ProxyServiceInterface;
use Domain\Product\Service\ScrapingOrchestratorInterface;
use Domain\Product\Service\ScrapingServiceInterface;
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
    const SCRAPING_SERVICE = GuzzleScrapper::class;
    const PRODUCT_MAPPER_SERVICE = ProductMapper::class;
    const SCRAPING_ORCHESTRATOR_SERVICE = ScrapingOrchestrator::class;

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

        // Register GuzzleScrapper as singleton
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
    }
}
