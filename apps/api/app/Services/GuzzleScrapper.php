<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Scrapers\AmazonScraper;
use App\Services\Scrapers\JumiaScraper;
use Domain\Product\Exception\ScrapingException;
use Domain\Product\Exception\UnsupportedPlatformException;
use Domain\Product\Service\PlatformScraperInterface;
use Domain\Product\Service\ProxyServiceInterface;
use Domain\Product\Service\ScrapedProductData;
use Domain\Product\Service\ScrapingServiceInterface;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\ProductUrl;
use Exception;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

/**
 * Guzzle Scrapper Service
 * 
 * Main scraping service that utilizes platform-specific scrapers (driver pattern)
 * and integrates with proxy service for request routing.
 * 
 * Requirements Implemented:
 * - REQ-SCRAPE-001: System SHALL implement a dedicated scraping service class
 * - REQ-SCRAPE-002: Service SHALL use Guzzle HTTP client for making requests
 * - REQ-SCRAPE-003: Service SHALL support scraping from Amazon and Jumia platforms
 * - REQ-SCRAPE-005: Service SHALL implement user-agent rotation
 * - REQ-SCRAPE-006: Service SHALL handle HTTP errors gracefully
 * - REQ-SCRAPE-009: Service SHALL log scraping activities and errors
 * - REQ-SCRAPE-010: Service SHALL support scraping a single product by URL
 * - REQ-SCRAPE-011: Service SHALL support scraping a list of products from multiple URLs
 * - REQ-INT-004: System SHALL use proxy rotation for all scraping requests
 * - REQ-ARCH-006: App layer SHALL implement services based on domain layer contracts
 */
class GuzzleScrapper implements ScrapingServiceInterface
{
    private ProxyServiceInterface $proxyService;
    private HttpClient $httpClient;
    private array $platformScrapers = [];

    public function __construct(
        ProxyServiceInterface $proxyService,
        HttpClient $httpClient
    ) {
        $this->proxyService = $proxyService;
        $this->httpClient = $httpClient;
        $this->initializePlatformScrapers();
        
        Log::info('[GUZZLE-SCRAPPER] Service initialized', [
            'supported_platforms' => array_keys($this->platformScrapers),
            'proxy_service_healthy' => $this->proxyService->isHealthy(),
        ]);
    }

    /**
     * Initialize platform-specific scrapers (Driver Pattern)
     * 
     * REQ-SCRAPE-012: Service SHALL differentiate between platform structures
     */
    private function initializePlatformScrapers(): void
    {
        $this->platformScrapers = [
            'amazon' => new AmazonScraper($this->httpClient),
            'jumia' => new JumiaScraper($this->httpClient),
        ];
    }

    /**
     * Scrape product data from a URL
     * 
     * REQ-SCRAPE-010: Service SHALL support scraping a single product by URL
     * REQ-SCRAPE-006: Service SHALL handle HTTP errors gracefully
     * REQ-SCRAPE-009: Service SHALL log scraping activities and errors
     * 
     * @param ProductUrl $url
     * @param Platform $platform
     * @return ScrapedProductData
     * @throws ScrapingException
     * @throws UnsupportedPlatformException
     */
    public function scrapeProduct(ProductUrl $url, Platform $platform): ScrapedProductData
    {
        Log::info('[GUZZLE-SCRAPPER] Starting product scrape', [
            'url' => $url->toString(),
            'platform' => $platform->toString(),
        ]);

        // Validate platform support
        if (!$this->supportsPlatform($platform)) {
            throw UnsupportedPlatformException::forPlatform($platform->toString());
        }

        // Validate URL matches platform
        if (!$url->matchesPlatform($platform)) {
            throw ScrapingException::failed(
                $url->toString(),
                "URL does not match platform {$platform->toString()}"
            );
        }

        // Get platform-specific scraper
        $scraper = $this->getScraperForPlatform($platform);

        // Log proxy service status
        $this->logProxyServiceStatus();

        try {
            // Perform scraping using platform-specific scraper
            $scrapedData = $scraper->scrape($url);

            Log::info('[GUZZLE-SCRAPPER] Successfully scraped product', [
                'url' => $url->toString(),
                'platform' => $platform->toString(),
                'title' => $scrapedData->getTitle(),
                'price' => $scrapedData->getPrice()->toFloat(),
                'currency' => $scrapedData->getPriceCurrency(),
            ]);

            return $scrapedData;

        } catch (ScrapingException $e) {
            Log::error('[GUZZLE-SCRAPPER] Scraping failed', [
                'url' => $url->toString(),
                'platform' => $platform->toString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Scrape multiple products from different URLs
     * 
     * REQ-SCRAPE-011: Service SHALL support scraping a list of products from multiple URLs
     * 
     * @param array $urlPlatformPairs Array of ['url' => ProductUrl, 'platform' => Platform]
     * @return array Array of ScrapedProductData or exceptions
     */
    public function scrapeMultipleProducts(array $urlPlatformPairs): array
    {
        Log::info('[GUZZLE-SCRAPPER] Starting bulk scraping', [
            'product_count' => count($urlPlatformPairs),
        ]);

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($urlPlatformPairs as $index => $pair) {
            $url = $pair['url'];
            $platform = $pair['platform'];

            try {
                $result = $this->scrapeProduct($url, $platform);
                $results[$index] = [
                    'status' => 'success',
                    'data' => $result,
                    'url' => $url->toString(),
                    'platform' => $platform->toString(),
                ];
                $successCount++;

            } catch (Exception $e) {
                $results[$index] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'url' => $url->toString(),
                    'platform' => $platform->toString(),
                ];
                $failureCount++;

                Log::warning('[GUZZLE-SCRAPPER] Product scraping failed in bulk operation', [
                    'index' => $index,
                    'url' => $url->toString(),
                    'platform' => $platform->toString(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[GUZZLE-SCRAPPER] Bulk scraping completed', [
            'total' => count($urlPlatformPairs),
            'success' => $successCount,
            'failed' => $failureCount,
            'success_rate' => $successCount > 0 ? round(($successCount / count($urlPlatformPairs)) * 100, 2) : 0,
        ]);

        return $results;
    }

    /**
     * Check if scraping is supported for a platform
     * 
     * @param Platform $platform
     * @return bool
     */
    public function supportsPlatform(Platform $platform): bool
    {
        return isset($this->platformScrapers[$platform->toString()]);
    }

    /**
     * Get the scraper implementation for a platform
     * 
     * @param Platform $platform
     * @return PlatformScraperInterface
     * @throws UnsupportedPlatformException
     */
    public function getScraperForPlatform(Platform $platform): PlatformScraperInterface
    {
        $platformName = $platform->toString();
        
        if (!isset($this->platformScrapers[$platformName])) {
            throw UnsupportedPlatformException::forPlatform($platformName);
        }

        return $this->platformScrapers[$platformName];
    }

    /**
     * Get list of supported platforms
     * 
     * @return array
     */
    public function getSupportedPlatforms(): array
    {
        return array_keys($this->platformScrapers);
    }

    /**
     * Get scraping statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        $proxyStatus = $this->proxyService->getStatus();
        
        return [
            'supported_platforms' => $this->getSupportedPlatforms(),
            'platform_scrapers' => array_map(function (PlatformScraperInterface $scraper) {
                return [
                    'platform' => $scraper->getPlatformName(),
                    'class' => get_class($scraper),
                ];
            }, $this->platformScrapers),
            'proxy_service' => [
                'healthy' => $proxyStatus->isHealthy(),
                'total_proxies' => $proxyStatus->getTotalProxies(),
                'healthy_proxies' => $proxyStatus->getHealthyProxies(),
            ],
        ];
    }

    /**
     * Test scraping capability for a platform
     * 
     * @param Platform $platform
     * @return array Test results
     */
    public function testPlatformScraping(Platform $platform): array
    {
        Log::info('[GUZZLE-SCRAPPER] Testing platform scraping', [
            'platform' => $platform->toString(),
        ]);

        if (!$this->supportsPlatform($platform)) {
            return [
                'platform' => $platform->toString(),
                'supported' => false,
                'error' => 'Platform not supported',
            ];
        }

        $scraper = $this->getScraperForPlatform($platform);
        $proxyServiceHealthy = $this->proxyService->isHealthy();

        return [
            'platform' => $platform->toString(),
            'supported' => true,
            'scraper_class' => get_class($scraper),
            'proxy_service_healthy' => $proxyServiceHealthy,
            'proxy_count' => $this->proxyService->getStatus()->getHealthyProxies(),
        ];
    }

    /**
     * Log proxy service status for debugging
     */
    private function logProxyServiceStatus(): void
    {
        try {
            $status = $this->proxyService->getStatus();
            
            Log::debug('[GUZZLE-SCRAPPER] Proxy service status', [
                'healthy' => $status->isHealthy(),
                'total_proxies' => $status->getTotalProxies(),
                'healthy_proxies' => $status->getHealthyProxies(),
                'message' => $status->getMessage(),
            ]);

        } catch (Exception $e) {
            Log::warning('[GUZZLE-SCRAPPER] Could not get proxy service status', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate URL and platform combination
     * 
     * @param ProductUrl $url
     * @param Platform $platform
     * @throws ScrapingException
     */
    private function validateUrlPlatformMatch(ProductUrl $url, Platform $platform): void
    {
        if (!$url->matchesPlatform($platform)) {
            throw ScrapingException::failed(
                $url->toString(),
                "URL does not match the specified platform: {$platform->toString()}"
            );
        }
    }

    /**
     * Get service health information
     * 
     * @return array
     */
    public function getHealthStatus(): array
    {
        $proxyStatus = $this->proxyService->getStatus();
        
        return [
            'service' => 'GuzzleScrapper',
            'status' => 'operational',
            'platforms' => [
                'supported' => $this->getSupportedPlatforms(),
                'total' => count($this->platformScrapers),
            ],
            'proxy_service' => [
                'healthy' => $proxyStatus->isHealthy(),
                'total_proxies' => $proxyStatus->getTotalProxies(),
                'healthy_proxies' => $proxyStatus->getHealthyProxies(),
                'unhealthy_proxies' => $proxyStatus->getTotalProxies() - $proxyStatus->getHealthyProxies(),
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}