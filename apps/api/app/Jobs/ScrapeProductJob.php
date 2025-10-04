<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ProductScrapingStorageService;
use Domain\Product\Entity\Product;
use Domain\Product\Exception\ScrapingException;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\ProductUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $productId,
        private readonly string $productUrl,
        private readonly string $productPlatform
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ProductScrapingStorageService $scrapingService): void
    {
        Log::info('[SCRAPE-PRODUCT-JOB] Starting product scraping', [
            'product_id' => $this->productId,
            'url' => $this->productUrl,
            'platform' => $this->productPlatform,
            'attempt' => $this->attempts(),
        ]);

        try {
            $url = ProductUrl::make($this->productUrl);
            $platform = Platform::fromString($this->productPlatform);
            
            $scrapingService->scrapeAndStore($url, $platform);

            Log::info('[SCRAPE-PRODUCT-JOB] Successfully scraped product', [
                'product_id' => $this->productId,
                'url' => $this->productUrl,
            ]);
        } catch (ScrapingException $e) {
            Log::error('[SCRAPE-PRODUCT-JOB] Scraping failed', [
                'product_id' => $this->productId,
                'url' => $this->productUrl,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        } catch (\Exception $e) {
            Log::error('[SCRAPE-PRODUCT-JOB] Unexpected error during scraping', [
                'product_id' => $this->productId,
                'url' => $this->productUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[SCRAPE-PRODUCT-JOB] Job failed after all retries', [
            'product_id' => $this->productId,
            'url' => $this->productUrl,
            'platform' => $this->productPlatform,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // TODO: You might want to send a notification or mark the product as failed
        // For now, we just log the failure
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'scraping',
            'product:' . $this->productId,
            'platform:' . $this->productPlatform,
        ];
    }
}
