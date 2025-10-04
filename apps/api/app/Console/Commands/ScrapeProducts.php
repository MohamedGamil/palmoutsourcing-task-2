<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ScheduleProductScrapingJob;
use Illuminate\Console\Command;

class ScrapeProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:products
                            {--batch-size=100 : Number of products to scrape in this batch}
                            {--max-hours=24 : Maximum hours since last scrape to consider outdated}
                            {--sync : Run synchronously without queuing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule scraping of products that need updating';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batchSize = (int) $this->option('batch-size');
        $maxHours = (int) $this->option('max-hours');
        $sync = (bool) $this->option('sync');

        $this->info("Scheduling product scraping...");
        $this->line("Batch Size: {$batchSize}");
        $this->line("Max Hours Since Last Scrape: {$maxHours}");
        $this->line("Mode: " . ($sync ? 'Synchronous' : 'Queue'));
        $this->newLine();

        try {
            if ($sync) {
                // Run synchronously (useful for testing)
                $this->info('Running scraping job synchronously...');
                $job = new ScheduleProductScrapingJob($batchSize, $maxHours);
                $job->handle();
            } else {
                // Dispatch to queue
                $this->info('Dispatching scraping job to queue...');
                ScheduleProductScrapingJob::dispatch($batchSize, $maxHours);
            }

            $this->newLine();
            $this->info('✓ Scraping job scheduled successfully!');
            
            if (!$sync) {
                $this->line('Check the queue worker logs to monitor progress.');
                $this->line('Run: php artisan queue:work');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Failed to schedule scraping job');
            $this->error('Error: ' . $e->getMessage());
            
            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
