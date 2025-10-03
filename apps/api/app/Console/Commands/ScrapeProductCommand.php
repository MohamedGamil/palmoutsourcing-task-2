<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Facades\ProductScrapingStorageService;
use App\Facades\ScrapingOrchestrator;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\ProductUrl;
use Illuminate\Console\Command;
use Exception;

/**
 * Scrape Product Command
 * 
 * Main artisan command for scraping products from Amazon or Jumia.
 * Supports scraping with optional database storage.
 * 
 * Usage:
 *   php artisan scrape {url}                    # Scrape and display (no storage)
 *   php artisan scrape {url} --store            # Scrape and store to database
 *   php artisan scrape {url} --json             # Output as JSON
 */
class ScrapeProductCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'scrape 
                          {url : Product URL from Amazon or Jumia}
                          {--store : Store the scraped product to database}
                          {--json : Output result as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'Scrape a product from Amazon or Jumia with optional database storage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $url = $this->argument('url');
        $shouldStore = $this->option('store');
        $jsonOutput = $this->option('json');
        $quiet = $this->option('quiet'); // Built-in Laravel option

        try {
            // Validate and parse URL
            if (!$quiet) {
                $this->info('🔍 Scraping Product...');
                $this->line("URL: {$url}");
                $this->newLine();
            }

            // Detect platform from URL
            $platform = $this->detectPlatform($url);
            
            if (!$quiet) {
                $this->line("Platform: " . ucfirst($platform));
            }

            // Scrape and map product
            $result = ScrapingOrchestrator::scrapeAndMapProduct($url);

            if ($result['status'] !== 'success') {
                $this->error('❌ Scraping failed: ' . ($result['error'] ?? 'Unknown error'));
                return 1;
            }

            $mappedData = $result['mapped_data'];
            $validation = $result['validation'];

            // Store to database if requested
            $storedProduct = null;
            if ($shouldStore) {
                if (!$quiet) {
                    $this->info('💾 Storing product to database...');
                }

                try {
                    $productUrl = ProductUrl::fromString($url);
                    $platformObj = Platform::fromString($platform);

                    $storedProduct = ProductScrapingStorageService::scrapeAndStore(
                        $productUrl,
                        $platformObj
                    );

                    if (!$quiet) {
                        $this->info('✅ Product stored successfully!');
                        $this->line("Database ID: {$storedProduct->getId()}");
                    }
                } catch (Exception $e) {
                    $this->error('❌ Storage failed: ' . $e->getMessage());
                    return 1;
                }
            }

            // Output results
            if ($jsonOutput) {
                $this->outputJson($mappedData, $validation, $storedProduct);
            } else {
                $this->displayProduct($mappedData, $validation, $storedProduct, $quiet);
            }

            if (!$quiet) {
                $this->newLine();
                $this->info('✅ Scraping completed successfully!');
            }

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }
            
            return 1;
        }
    }

    /**
     * Detect platform from URL
     */
    private function detectPlatform(string $url): string
    {
        $url = strtolower($url);

        if (str_contains($url, 'amazon.')) {
            return 'amazon';
        }

        if (str_contains($url, 'jumia.')) {
            return 'jumia';
        }

        throw new Exception(
            'Could not detect platform from URL. ' .
            'Supported platforms: Amazon (amazon.com, amazon.co.uk, etc.) and Jumia (jumia.com.eg, jumia.co.ke, etc.)'
        );
    }

    /**
     * Display product information in a formatted table
     */
    private function displayProduct(array $mappedData, array $validation, $storedProduct, bool $quiet): void
    {
        if ($quiet) {
            return;
        }

        $this->newLine();
        $this->line('📦 <fg=cyan;options=bold>Product Information</>');
        $this->line(str_repeat('─', 70));

        $this->table(
            ['Field', 'Value'],
            [
                ['Title', $this->truncate($mappedData['title'], 60)],
                ['Price', $mappedData['price'] . ' ' . $mappedData['currency']],
                ['Platform', ucfirst($mappedData['platform'])],
                ['Platform ID', $mappedData['platform_id'] ?? 'N/A'],
                ['Category', $mappedData['category'] ?? 'N/A'],
                ['Rating', $mappedData['rating'] ? $mappedData['rating'] . ' / 5' : 'N/A'],
                ['Rating Count', $mappedData['rating_count'] ?? 'N/A'],
                ['Image URL', $mappedData['image_url'] ? $this->truncate($mappedData['image_url'], 50) : 'N/A'],
            ]
        );

        // Display validation information
        $this->newLine();
        $this->line('🔍 <fg=cyan;options=bold>Data Quality</>');
        $this->line(str_repeat('─', 70));
        
        $completeness = $validation['completeness_score'] * 100;
        $this->line("Completeness Score: <fg=yellow>{$completeness}%</>");
        
        if ($validation['valid']) {
            $this->line("Validation: <fg=green>✓ Passed</>");
        } else {
            $this->line("Validation: <fg=red>✗ Issues Found</>");
            foreach ($validation['errors'] as $error) {
                $this->line("  - <fg=red>{$error}</>");
            }
        }

        // Display storage information if stored
        if ($storedProduct) {
            $this->newLine();
            $this->line('💾 <fg=cyan;options=bold>Database Storage</>');
            $this->line(str_repeat('─', 70));
            
            $this->table(
                ['Field', 'Value'],
                [
                    ['Database ID', $storedProduct->getId()],
                    ['Active', $storedProduct->isActive() ? 'Yes' : 'No'],
                    ['Scrape Count', $storedProduct->getScrapeCount()],
                    ['Last Scraped', $storedProduct->getLastScrapedAt()?->format('Y-m-d H:i:s') ?? 'Never'],
                    ['Created At', $storedProduct->getCreatedAt()->format('Y-m-d H:i:s')],
                ]
            );
        }
    }

    /**
     * Output product data as JSON
     */
    private function outputJson(array $mappedData, array $validation, $storedProduct): void
    {
        $output = [
            'status' => 'success',
            'product' => $mappedData,
            'validation' => $validation,
        ];

        if ($storedProduct) {
            $output['stored'] = [
                'id' => $storedProduct->getId(),
                'active' => $storedProduct->isActive(),
                'scrape_count' => $storedProduct->getScrapeCount(),
                'last_scraped_at' => $storedProduct->getLastScrapedAt()?->format('Y-m-d H:i:s'),
                'created_at' => $storedProduct->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Truncate string with ellipsis
     */
    private function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - 3) . '...';
    }
}
