<?php

declare(strict_types=1);

namespace App\Facades;

use Domain\Product\Service\ProxyInfo;
use Domain\Product\Service\ProxyServiceStatus;
use Illuminate\Support\Facades\Facade;

/**
 * Proxy Service Facade
 * 
 * Provides easy access to the proxy service throughout the application.
 * 
 * @method static ProxyInfo|null getNextProxy()
 * @method static ProxyInfo[] getAllProxies()
 * @method static bool isHealthy()
 * @method static ProxyServiceStatus getStatus()
 * 
 * @see \App\Services\ProxyService
 */
class ProxyService extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Domain\Product\Service\ProxyServiceInterface::class;
    }
}