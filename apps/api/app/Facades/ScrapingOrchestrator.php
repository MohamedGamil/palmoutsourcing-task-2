<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Scraping Orchestrator Facade
 * 
 * Provides static access to the ScrapingOrchestrator service for convenient usage
 * throughout the application without explicit dependency injection.
 * 
 * @method static array scrapeAndMapProduct(string $url)
 * @method static array scrapeAndMapMultipleProducts(array $urls)
 * @method static array testPlatformCapability(string $platformName)
 * @method static array getHealthStatus()
 * @method static array getStatistics()
 * 
 * @see \App\Services\ScrapingOrchestrator
 */
class ScrapingOrchestrator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Domain\Product\Service\ScrapingOrchestratorInterface::class;
    }
}