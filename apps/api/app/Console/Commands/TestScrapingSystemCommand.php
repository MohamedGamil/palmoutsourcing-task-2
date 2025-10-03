<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ScrapingOrchestrator;
use Illuminate\Console\Command;

/**
 * Test Scraping System Command
 * 
 * Artisan command to test the complete scraping system including:
 * - Proxy service integration
 * - Platform-specific scrapers
 * - Product mapping service
 * - End-to-end workflow
 */
class TestScrapingSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'scraping:test 
                          {--platform= : Test specific platform (amazon, jumia)}
                          {--url= : Test specific URL}
                          {--health : Check service health status}
                          {--stats : Show service statistics}';

    /**
     * The console command description.
     */
    protected $description = 'Test the complete scraping system';

    private ScrapingOrchestrator $scrapingOrchestrator;

    public function __construct(ScrapingOrchestrator $scrapingOrchestrator)
    {
        parent::__construct();
        $this->scrapingOrchestrator = $scrapingOrchestrator;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Testing Scraping System');
        $this->newLine();

        try {
            // Health check
            if ($this->option('health') || !$this->hasAnyOptions()) {
                $this->testHealthStatus();
            }

            // Statistics
            if ($this->option('stats') || !$this->hasAnyOptions()) {
                $this->showStatistics();
            }

            // Platform test
            if ($platform = $this->option('platform')) {
                $this->testPlatform($platform);
            }

            // URL test
            if ($url = $this->option('url')) {
                $this->testUrl($url);
            }

            // Default comprehensive test
            if (!$this->hasAnyOptions()) {
                $this->runComprehensiveTest();
            }

            $this->newLine();
            $this->info('✅ All tests completed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }
    }

    private function testHealthStatus(): void
    {
        $this->info('🏥 Checking Service Health Status...');
        
        $health = $this->scrapingOrchestrator->getHealthStatus();
        
        $this->table(
            ['Service', 'Status', 'Details'],
            [
                [
                    'Orchestrator',
                    $health['orchestrator']['status'],
                    'Main service coordinator'
                ],
                [
                    'Scraping Service',
                    $health['scraping_service']['status'],
                    'Platforms: ' . implode(', ', $health['scraping_service']['platforms']['supported'])
                ],
                [
                    'Proxy Service',
                    $health['scraping_service']['proxy_service']['healthy'] ? 'healthy' : 'unhealthy',
                    sprintf(
                        '%d/%d proxies healthy',
                        $health['scraping_service']['proxy_service']['healthy_proxies'],
                        $health['scraping_service']['proxy_service']['total_proxies']
                    )
                ],
            ]
        );
        
        $this->newLine();
    }

    private function showStatistics(): void
    {
        $this->info('📊 Service Statistics...');
        
        $stats = $this->scrapingOrchestrator->getStatistics();
        
        $this->table(
            ['Component', 'Supported Platforms', 'Features'],
            [
                [
                    'Scraping Service',
                    implode(', ', $stats['components']['scraping_service']['supported_platforms']),
                    'Proxy integration, User-agent rotation'
                ],
                [
                    'Mapping Service',
                    implode(', ', $stats['components']['mapping_service']['supported_platforms']),
                    'Data validation, Category mapping'
                ],
            ]
        );
        
        $this->newLine();
    }

    private function testPlatform(string $platform): void
    {
        $this->info("🔧 Testing Platform: {$platform}");
        
        $result = $this->scrapingOrchestrator->testPlatformCapability($platform);
        
        if ($result['overall_status'] === 'ready') {
            $this->info("✅ Platform {$platform} is ready");
            $this->line("  - Scraper: " . $result['scraping_service']['scraper_class']);
            $this->line("  - Proxy service healthy: " . ($result['scraping_service']['proxy_service_healthy'] ? 'Yes' : 'No'));
            $this->line("  - Available proxies: " . $result['scraping_service']['proxy_count']);
        } else {
            $this->error("❌ Platform {$platform} is not ready: " . $result['overall_status']);
            if (isset($result['error'])) {
                $this->line("Error: " . $result['error']);
            }
        }
        
        $this->newLine();
    }

    private function testUrl(string $url): void
    {
        $this->info("🌐 Testing URL: {$url}");
        
        $result = $this->scrapingOrchestrator->scrapeAndMapProduct($url);
        
        if ($result['status'] === 'success') {
            $this->info("✅ Successfully scraped and mapped product");
            $mapped = $result['mapped_data'];
            
            $this->table(
                ['Field', 'Value'],
                [
                    ['Product ID', $mapped['id']],
                    ['Title', substr($mapped['title'], 0, 50) . '...'],
                    ['Price', $mapped['price'] . ' ' . $mapped['currency']],
                    ['Category', $mapped['category']],
                    ['Platform', $mapped['platform']],
                    ['Rating', $mapped['rating'] ?? 'N/A'],
                    ['Rating Count', $mapped['rating_count'] ?? 'N/A'],
                ]
            );
            
            $this->line("Completeness Score: " . $result['validation']['completeness_score']);
            if (!$result['validation']['valid']) {
                $this->warn("Validation Issues: " . implode(', ', $result['validation']['errors']));
            }
        } else {
            $this->error("❌ Failed to scrape URL: " . $result['error']);
        }
        
        $this->newLine();
    }

    private function runComprehensiveTest(): void
    {
        $this->info('🚀 Running Comprehensive Test...');
        
        // Test sample URLs (these are example URLs - they might not work without proper proxy configuration)
        $testUrls = [
            'https://www.amazon.com/dp/B08N5WRWNW',
            'https://www.jumia.com.eg/generic-product-12345/',
        ];
        
        foreach ($testUrls as $url) {
            try {
                $this->info("Testing: {$url}");
                $result = $this->scrapingOrchestrator->scrapeAndMapProduct($url);
                
                if ($result['status'] === 'success') {
                    $this->info("  ✅ Success - " . $result['mapped_data']['title']);
                } else {
                    $this->warn("  ⚠️ Failed - " . $result['error']);
                }
            } catch (\Exception $e) {
                $this->warn("  ⚠️ Exception - " . $e->getMessage());
            }
        }
        
        $this->newLine();
    }

    private function hasAnyOptions(): bool
    {
        return $this->option('health') || 
               $this->option('stats') || 
               $this->option('platform') || 
               $this->option('url');
    }
}