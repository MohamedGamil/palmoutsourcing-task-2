<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Product Mapper Facade
 * 
 * Provides static access to the ProductMapper service for convenient usage
 * throughout the application without explicit dependency injection.
 * 
 * @method static array mapToProduct(\Domain\Product\Service\ScrapedProductData $scrapedData, \Domain\Product\ValueObject\Platform $platform, \Domain\Product\ValueObject\ProductUrl $originalUrl)
 * @method static array mapMultipleProducts(array $scrapedDataArray)
 * @method static array validateScrapedData(\Domain\Product\Service\ScrapedProductData $scrapedData)
 * @method static array getStatistics()
 * 
 * @see \App\Services\ProductMapper
 */
class ProductMapper extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Domain\Product\Service\ProductMapperInterface::class;
    }
}