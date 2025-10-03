<?php

declare(strict_types=1);

namespace App\Services;

use Domain\Product\Service\ProductMapperInterface;
use Domain\Product\Service\ScrapingOrchestratorInterface;
use Domain\Product\Service\ScrapingServiceInterface;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\ProductUrl;
use Illuminate\Support\Facades\Log;

/**
 * Scraping Orchestrator Service
 * 
 * Orchestrates the complete scraping and mapping workflow by coordinating
 * between GuzzleScrapper and ProductMapper services.
 * 
 * This service demonstrates the complete integration of:
 * - Proxy service for request routing
 * - Platform-specific scrapers for data extraction
 * - Product mapper for data transformation
 * 
 * Requirements Implemented:
 * - REQ-INT-001: System SHALL integrate scraping and mapping services
 * - REQ-INT-002: System SHALL provide a unified interface for scraping operations
 * - REQ-INT-003: System SHALL support end-to-end product data processing
 */
class ScrapingOrchestrator implements ScrapingOrchestratorInterface
{
    private ScrapingServiceInterface $guzzleScrapper;
    private ProductMapperInterface $productMapper;

    public function __construct(
        ScrapingServiceInterface $guzzleScrapper,
        ProductMapperInterface $productMapper
    ) {
        $this->guzzleScrapper = $guzzleScrapper;
        $this->productMapper = $productMapper;
    }

    /**
     * Scrape and map a single product
     * 
     * @param string $url Product URL to scrape
     * @return array Complete product data with mapping
     */
    public function scrapeAndMapProduct(string $url): array
    {
        Log::info('[SCRAPING-ORCHESTRATOR] Starting complete product processing', [
            'url' => $url,
        ]);

        try {
            // Create domain objects
            $productUrl = ProductUrl::fromString($url);
            $platform = $productUrl->detectPlatform();

            Log::info('[SCRAPING-ORCHESTRATOR] Detected platform', [
                'url' => $url,
                'platform' => $platform->toString(),
            ]);

            // Check if platform is supported
            if (!$this->guzzleScrapper->supportsPlatform($platform)) {
                throw new \Exception("Platform {$platform->toString()} is not supported");
            }

            // Scrape product data
            $scrapedData = $this->guzzleScrapper->scrapeProduct($productUrl, $platform);

            Log::info('[SCRAPING-ORCHESTRATOR] Product scraped successfully', [
                'title' => $scrapedData->getTitle(),
                'price' => $scrapedData->getPrice()->toFloat(),
                'currency' => $scrapedData->getPriceCurrency(),
            ]);

            // Validate scraped data
            $validation = $this->productMapper->validateScrapedData($scrapedData);
            
            if (!$validation['valid']) {
                Log::warning('[SCRAPING-ORCHESTRATOR] Scraped data validation failed', [
                    'url' => $url,
                    'errors' => $validation['errors'],
                    'completeness_score' => $validation['completeness_score'],
                ]);
            }

            // Map to structured format
            $mappedProduct = $this->productMapper->mapToProduct($scrapedData, $platform, $productUrl);

            Log::info('[SCRAPING-ORCHESTRATOR] Product mapped successfully', [
                'product_id' => $mappedProduct['id'],
                'title' => $mappedProduct['title'],
                'category' => $mappedProduct['category'],
            ]);

            return [
                'status' => 'success',
                'raw_data' => $scrapedData->toArray(),
                'mapped_data' => $mappedProduct,
                'validation' => $validation,
                'processing_info' => [
                    'platform' => $platform->toString(),
                    'scraper_class' => get_class($this->guzzleScrapper->getScraperForPlatform($platform)),
                    'processed_at' => now()->toISOString(),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('[SCRAPING-ORCHESTRATOR] Product processing failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'url' => $url,
                'processed_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Scrape and map multiple products
     * 
     * @param array $urls Array of product URLs
     * @return array Results for each URL
     */
    public function scrapeAndMapMultipleProducts(array $urls): array
    {
        Log::info('[SCRAPING-ORCHESTRATOR] Starting batch processing', [
            'url_count' => count($urls),
        ]);

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($urls as $index => $url) {
            $result = $this->scrapeAndMapProduct($url);
            $results[$index] = $result;

            if ($result['status'] === 'success') {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        Log::info('[SCRAPING-ORCHESTRATOR] Batch processing completed', [
            'total' => count($urls),
            'success' => $successCount,
            'failed' => $failureCount,
            'success_rate' => $successCount > 0 ? round(($successCount / count($urls)) * 100, 2) : 0,
        ]);

        return [
            'summary' => [
                'total' => count($urls),
                'success' => $successCount,
                'failed' => $failureCount,
                'success_rate' => $successCount > 0 ? round(($successCount / count($urls)) * 100, 2) : 0,
            ],
            'results' => $results,
            'processed_at' => now()->toISOString(),
        ];
    }

    /**
     * Test scraping capability for a platform
     * 
     * @param string $platformName Platform to test
     * @return array Test results
     */
    public function testPlatformCapability(string $platformName): array
    {
        Log::info('[SCRAPING-ORCHESTRATOR] Testing platform capability', [
            'platform' => $platformName,
        ]);

        try {
            $platform = Platform::fromString($platformName);
            
            // Test scraping service
            $scrapingTest = $this->guzzleScrapper->testPlatformScraping($platform);
            
            // Test mapping service statistics
            $mappingStats = $this->productMapper->getStatistics();
            
            return [
                'platform' => $platformName,
                'scraping_service' => $scrapingTest,
                'mapping_service' => [
                    'supported_platforms' => $mappingStats['supported_platforms'],
                    'category_mappings' => $mappingStats['category_mappings'][$platformName] ?? [],
                ],
                'overall_status' => $scrapingTest['supported'] ? 'ready' : 'not_supported',
                'tested_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            return [
                'platform' => $platformName,
                'overall_status' => 'error',
                'error' => $e->getMessage(),
                'tested_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get complete service health status
     * 
     * @return array Health information for all services
     */
    public function getHealthStatus(): array
    {
        $scrapingHealth = $this->guzzleScrapper->getHealthStatus();
        $mappingStats = $this->productMapper->getStatistics();

        return [
            'orchestrator' => [
                'service' => 'ScrapingOrchestrator',
                'status' => 'operational',
            ],
            'scraping_service' => $scrapingHealth,
            'mapping_service' => $mappingStats,
            'integration' => [
                'supported_platforms' => $scrapingHealth['platforms']['supported'],
                'total_pipeline_components' => 3, // Proxy + Scraping + Mapping
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get service statistics
     * 
     * @return array Comprehensive statistics
     */
    public function getStatistics(): array
    {
        return [
            'service' => 'ScrapingOrchestrator',
            'components' => [
                'scraping_service' => $this->guzzleScrapper->getStatistics(),
                'mapping_service' => $this->productMapper->getStatistics(),
            ],
            'capabilities' => [
                'supported_platforms' => $this->guzzleScrapper->getSupportedPlatforms(),
                'proxy_integration' => true,
                'user_agent_rotation' => true,
                'data_validation' => true,
                'batch_processing' => true,
            ],
            'generated_at' => now()->toISOString(),
        ];
    }
}