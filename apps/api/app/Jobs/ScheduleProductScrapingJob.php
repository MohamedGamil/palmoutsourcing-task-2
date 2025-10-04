<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Facades\ProductRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScheduleProductScrapingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $batchSize = 100,
        private readonly int $maxHoursSinceLastScrape = 24
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[SCHEDULE-SCRAPING-JOB] Starting batch scraping schedule', [
            'batch_size' => $this->batchSize,
            'max_hours_since_last_scrape' => $this->maxHoursSinceLastScrape,
        ]);

        try {
            // Fetch products needing scraping with intelligent prioritization
            $products = ProductRepository::findProductsForScraping(
                $this->batchSize,
                $this->maxHoursSinceLastScrape
            );

            if (empty($products)) {
                Log::info('[SCHEDULE-SCRAPING-JOB] No products need scraping');
                return;
            }

            Log::info('[SCHEDULE-SCRAPING-JOB] Found products to scrape', [
                'count' => count($products),
            ]);

            // Dispatch individual scraping jobs for each product
            $dispatchedCount = 0;
            foreach ($products as $product) {
                ScrapeProductJob::dispatch(
                    $product->getId(),
                    $product->getProductUrl()->getOriginalUrl(),
                    $product->getPlatform()->toString()
                );
                $dispatchedCount++;
            }

            Log::info('[SCHEDULE-SCRAPING-JOB] Successfully dispatched scraping jobs', [
                'dispatched_count' => $dispatchedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('[SCHEDULE-SCRAPING-JOB] Error during batch scheduling', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[SCHEDULE-SCRAPING-JOB] Job failed', [
            'batch_size' => $this->batchSize,
            'error' => $exception->getMessage(),
        ]);

        // TODO: Send notification about batch scheduling failure
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
            'batch-scheduling',
        ];
    }
}
