<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Scraping Service Facade
 * 
 * Provides static access to the ScrapingService service for convenient usage
 * throughout the application without explicit dependency injection.
 * 
 * @method static bool supportsPlatform(\Domain\Product\ValueObject\Platform $platform)
 * @method static \Domain\Product\Service\ScrapedProductData scrapeProduct(\Domain\Product\ValueObject\ProductUrl $url, \Domain\Product\ValueObject\Platform $platform)
 * @method static array scrapeMultipleProducts(array $urlPlatformPairs)
 * @method static \Domain\Product\Service\PlatformScraperInterface getScraperForPlatform(\Domain\Product\ValueObject\Platform $platform)
 * @method static array getSupportedPlatforms()
 * @method static array getStatistics()
 * @method static array testPlatformScraping(\Domain\Product\ValueObject\Platform $platform)
 * @method static array getHealthStatus()
 * 
 * @see \App\Services\ScrapingService
 */
class ScrapingService extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Domain\Product\Service\ScrapingServiceInterface::class;
    }
}