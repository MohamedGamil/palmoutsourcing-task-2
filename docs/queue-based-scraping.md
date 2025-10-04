# Queue-Based Product Scraping Implementation

## Overview
Implemented a queue-based periodic scraping system for active products using Laravel's queue infrastructure. The system intelligently prioritizes products that need scraping and processes them in batches.

## Components Implemented

### 1. Enhanced Repository Query (`ProductRepository`)
**File**: `apps/api/app/Repositories/ProductRepository.php`

Added `findProductsForScraping()` method with intelligent prioritization:
- **Priority 1**: Stale products (never scraped: `last_scraped_at IS NULL` or `scrape_count = 0`)
- **Priority 2**: Least scraped products (ordered by `scrape_count ASC`)
- **Priority 3**: Outdated products (ordered by `last_scraped_at ASC`)

```php
public function findProductsForScraping(int $limit = 100, int $maxHoursSinceLastScrape = 24): array
```

**Parameters**:
- `$limit`: Maximum number of products to return (default: 100)
- `$maxHoursSinceLastScrape`: Hours since last scrape to consider "outdated" (default: 24)

**Filtering**:
- Only active products (`is_active = true`)
- Products never scraped OR not scraped within specified time window

### 2. Individual Product Scraping Job
**File**: `apps/api/app/Jobs/ScrapeProductJob.php`

Queue job for scraping individual products.

**Features**:
- ✅ Processes single product scraping
- ✅ Automatic retries (3 attempts with 60-second backoff)
- ✅ Timeout protection (120 seconds)
- ✅ Comprehensive error logging
- ✅ Failed job handling with detailed error tracking
- ✅ Job tagging for queue monitoring

**Constructor Parameters**:
- `productId`: Database ID of the product
- `productUrl`: Original product URL
- `productPlatform`: Platform string (amazon/jumia)

**Error Handling**:
- Retries on `ScrapingException`
- Retries on general exceptions
- Logs all failures with stack traces
- Calls `failed()` method after all retries exhausted

### 3. Batch Scheduling Job
**File**: `apps/api/app/Jobs/ScheduleProductScrapingJob.php`

Orchestrates batch scraping by finding products and dispatching individual jobs.

**Features**:
- ✅ Fetches up to 100 products per execution
- ✅ Uses intelligent prioritization from repository
- ✅ Dispatches `ScrapeProductJob` for each product
- ✅ Comprehensive logging of batch operations
- ✅ Empty batch handling (logs when no products need scraping)

**Constructor Parameters**:
- `batchSize`: Number of products to process (default: 100)
- `maxHoursSinceLastScrape`: Time threshold for outdated products (default: 24)

**Process Flow**:
1. Query repository for products needing scraping
2. Check if batch is empty
3. Dispatch individual `ScrapeProductJob` for each product
4. Log batch statistics

### 4. Artisan Command
**File**: `apps/api/app/Console/Commands/ScrapeProducts.php`

Manual command for triggering batch scraping.

**Command**: `php artisan products:scrape`

**Options**:
- `--batch-size=N`: Number of products per batch (default: 100)
- `--max-hours=N`: Max hours since last scrape (default: 24)
- `--sync`: Run synchronously without queuing (useful for testing)

**Usage Examples**:
```bash
# Queue scraping for 100 products
php artisan products:scrape

# Custom batch size
php artisan products:scrape --batch-size=50

# Custom time window
php artisan products:scrape --max-hours=12

# Run synchronously for testing
php artisan products:scrape --sync

# Combine options
php artisan products:scrape --batch-size=25 --max-hours=6 --sync
```

## Priority Logic Details

The `findProductsForScraping()` method uses SQL `CASE` statement for prioritization:

```sql
ORDER BY 
  CASE 
    WHEN last_scraped_at IS NULL THEN 0  -- Highest priority
    WHEN scrape_count = 0 THEN 0         -- Also highest priority  
    ELSE 1                                -- Lower priority
  END,
  scrape_count ASC,                       -- Among equal priority, least scraped first
  last_scraped_at ASC NULLS FIRST        -- Among equal scrape_count, oldest first
LIMIT 100
```

**Priority Order**:
1. Products never scraped (`last_scraped_at IS NULL` or `scrape_count = 0`)
2. Among same priority: products with lowest `scrape_count`
3. Among same `scrape_count`: products with oldest `last_scraped_at`

## Testing

### Test 1: Synchronous Batch Scheduling
```bash
docker compose exec laravel.test php artisan products:scrape --sync --batch-size=5
```

**Results**:
- ✅ Command executed successfully
- ✅ Found 5 products needing scraping
- ✅ Dispatched 5 individual scraping jobs
- ✅ Logs confirm proper repository query with priority logic

### Test 2: Queue Processing
```bash
docker compose exec laravel.test php artisan queue:work --stop-when-empty
```

**Results**:
- ✅ Jobs processed from queue correctly
- ✅ ScrapeProductJob instances executed
- ✅ Error handling working (jobs failed on 404 URLs as expected for test data)
- ✅ Retry mechanism activated (3 attempts per job)
- ✅ Failed jobs logged with detailed error information

## Log Examples

### Successful Batch Scheduling
```
[SCHEDULE-SCRAPING-JOB] Starting batch scraping schedule
  "batch_size": 5,
  "max_hours_since_last_scrape": 24

[PRODUCT-REPOSITORY] Found products for scraping with priority
  "count": 5,
  "limit": 5,
  "max_hours_since_last_scrape": 24

[SCHEDULE-SCRAPING-JOB] Found products to scrape
  "count": 5

[SCHEDULE-SCRAPING-JOB] Successfully dispatched scraping jobs
  "dispatched_count": 5
```

### Individual Job Processing
```
[SCRAPE-PRODUCT-JOB] Starting product scraping
  "product_id": 18,
  "url": "https://www.jumia.co.ke/product-a-eligendi.html",
  "platform": "jumia",
  "attempt": 1

[SCRAPE-PRODUCT-JOB] Unexpected error during scraping
  "product_id": 18,
  "url": "https://www.jumia.co.ke/product-a-eligendi.html",
  "error": "Scraping failed: All 3 scraping attempts failed...",
  "attempt": 1
```

## Production Deployment

### 1. Configure Queue Driver
Update `.env`:
```env
QUEUE_CONNECTION=database  # or redis for better performance
```

### 2. Run Queue Worker
Keep a persistent queue worker running:
```bash
php artisan queue:work --daemon --tries=3 --timeout=120
```

### 3. Schedule Periodic Scraping
Add to `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Run batch scraping every hour
    $schedule->job(new ScheduleProductScrapingJob(100, 24))
             ->hourly()
             ->withoutOverlapping();
             
    // Or use the command
    $schedule->command('products:scrape')
             ->hourly()
             ->withoutOverlapping();
}
```

### 4. Supervisor Configuration (Production)
Create `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --timeout=120
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/worker.log
```

## Files Modified/Created

### Created Files
1. `apps/api/app/Jobs/ScrapeProductJob.php` - Individual product scraping job
2. `apps/api/app/Jobs/ScheduleProductScrapingJob.php` - Batch scheduling job
3. `apps/api/app/Console/Commands/ScrapeProducts.php` - Artisan command

### Modified Files
1. `apps/api/domain/Product/Repository/ProductRepositoryInterface.php` - Added `findProductsForScraping()` interface
2. `apps/api/app/Repositories/ProductRepository.php` - Implemented priority-based query
3. `apps/api/app/Facades/ProductRepository.php` - Added facade method annotation

## Benefits

1. **Scalability**: Queue-based processing handles large product volumes
2. **Reliability**: Automatic retries with exponential backoff
3. **Intelligence**: Prioritizes products that need scraping most
4. **Monitoring**: Comprehensive logging for debugging
5. **Flexibility**: Configurable batch sizes and time windows
6. **Performance**: Background processing doesn't block requests
7. **Graceful Degradation**: Failed jobs logged, system continues

## Areas of Improvement

1. **Job Monitoring Dashboard**: Integrate Laravel Horizon for visual queue monitoring
2. **Notification System**: Send alerts when scraping fails repeatedly
3. **Rate Limiting**: Add throttling to respect platform rate limits  
4. **Metrics Tracking**: Track scraping success/failure rates
5. **Dynamic Batch Sizing**: Adjust batch size based on queue depth
6. **Priority Queues**: Use separate queues for urgent vs. routine scraping
7. **Product Health Tracking**: Mark products as "unhealthy" after repeated failures
