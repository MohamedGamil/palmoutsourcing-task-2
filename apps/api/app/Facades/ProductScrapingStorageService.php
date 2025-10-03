<?php

declare(strict_types=1);

namespace App\Facades;

use Domain\Product\Entity\Product;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\ProductUrl;
use Illuminate\Support\Facades\Facade;

/**
 * ProductScrapingStorageService Facade
 * 
 * Provides static access to the ProductScrapingStorageService.
 * 
 * @method static Product scrapeAndStore(ProductUrl $url, Platform $platform)
 * @method static array scrapeAndStoreMultiple(array $urlPlatformPairs)
 * @method static array updateProductsNeedingScraping(int $maxHoursSinceLastScrape = 24)
 * @method static array getStorageStatistics()
 */
class ProductScrapingStorageService extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Domain\Product\Service\ProductScrapingStorageServiceInterface::class;
    }
}