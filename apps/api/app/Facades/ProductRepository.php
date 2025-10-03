<?php

declare(strict_types=1);

namespace App\Facades;

use Domain\Product\Entity\Product;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\ProductUrl;
use Domain\Product\Exception\ProductNotFoundException;
use Illuminate\Support\Facades\Facade;

/**
 * ProductRepository Facade
 * 
 * Provides static access to the ProductRepository service.
 * 
 * @method static Product findById(int $id)
 * @method static Product|null findByIdOrNull(int $id)
 * @method static Product findByUrl(ProductUrl $url, Platform $platform)
 * @method static Product|null findByUrlOrNull(ProductUrl $url, Platform $platform)
 * @method static Product save(Product $product)
 * @method static void delete(Product $product)
 * @method static Product[] findAllActive()
 * @method static Product[] findActiveByPlatform(Platform $platform)
 * @method static Product[] findProductsNeedingScraping(int $maxHoursSinceLastScrape = 24)
 * @method static int count()
 * @method static int countActive()
 * @method static int countByPlatform(Platform $platform)
 * @method static bool existsByUrl(ProductUrl $url, Platform $platform)
 */
class ProductRepository extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Domain\Product\Repository\ProductRepositoryInterface::class;
    }
}