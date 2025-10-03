# Scrape Command Usage Guide

## Overview
The `scrape` command is a Laravel artisan command that allows you to scrape product information from Amazon or Jumia and optionally store it in the database.

## Command Signature

```bash
php artisan scrape {url} [options]
```

## Arguments

- **url** (required): Full product URL from Amazon or Jumia

## Options

- `--store`: Store the scraped product to the database
- `--update`: Update existing product if found (requires `--store`)
- `--json`: Output result as JSON format
- `-q, --quiet`: Suppress all output except errors (built-in Laravel option)
- `-v, --verbose`: Increase verbosity for debugging

## Usage Examples

### 1. Scrape and Display (No Storage)

Scrape product and display information without storing to database:

```bash
php artisan scrape "https://www.amazon.com/dp/B08N5WRWNW"
```

**Output:**
```
🔍 Scraping Product...
URL: https://www.amazon.com/dp/B08N5WRWNW

Platform: Amazon

📦 Product Information
──────────────────────────────────────────────────────────────────
Field         | Value
──────────────────────────────────────────────────────────────────
Title         | Sony WH-1000XM4 Wireless Headphones
Price         | 349.99 USD
Platform      | Amazon
Platform ID   | B08N5WRWNW
Category      | Electronics
Rating        | 4.7 / 5
Rating Count  | 25000
Image URL     | https://m.media-amazon.com/images/I/...

🔍 Data Quality
──────────────────────────────────────────────────────────────────
Completeness Score: 100%
Validation: ✓ Passed

✅ Scraping completed successfully!
```

### 2. Scrape and Store to Database

Scrape product and save it to the database:

```bash
php artisan scrape "https://www.amazon.com/dp/B08N5WRWNW" --store
```

**Output includes storage information:**
```
💾 Storing product to database...
✅ Product stored successfully!
Database ID: 123

💾 Database Storage
──────────────────────────────────────────────────────────────────
Field         | Value
──────────────────────────────────────────────────────────────────
Database ID   | 123
Active        | Yes
Scrape Count  | 1
Last Scraped  | 2025-10-04 15:30:45
Created At    | 2025-10-04 15:30:45
```

### 3. Scrape Jumia Product

```bash
php artisan scrape "https://www.jumia.com.eg/product-name-12345.html" --store
```

### 4. JSON Output

Get JSON output for integration with other tools:

```bash
php artisan scrape "https://www.amazon.com/dp/B08N5WRWNW" --json
```

**Output:**
```json
{
    "status": "success",
    "product": {
        "id": "AMAZON_abc123def456",
        "title": "Sony WH-1000XM4 Wireless Headphones",
        "price": 349.99,
        "currency": "USD",
        "category": "Electronics",
        "platform": "amazon",
        "platform_id": "B08N5WRWNW",
        "original_url": "https://www.amazon.com/dp/B08N5WRWNW",
        "image_url": "https://m.media-amazon.com/...",
        "rating": 4.7,
        "rating_count": 25000,
        "platform_category": "Electronics",
        "created_at": "2025-10-04T15:30:45.000000Z"
    },
    "validation": {
        "valid": true,
        "errors": [],
        "completeness_score": 1
    }
}
```

### 5. JSON with Storage

```bash
php artisan scrape "https://www.amazon.com/dp/B08N5WRWNW" --store --json
```

**Output includes stored product information:**
```json
{
    "status": "success",
    "product": { ... },
    "validation": { ... },
    "stored": {
        "id": 123,
        "active": true,
        "scrape_count": 1,
        "last_scraped_at": "2025-10-04 15:30:45",
        "created_at": "2025-10-04 15:30:45"
    }
}
```

### 6. Quiet Mode

Suppress all output except errors:

```bash
php artisan scrape "https://www.amazon.com/dp/B08N5WRWNW" --store --quiet
```

Only errors will be displayed if they occur.

### 7. Using with Sail (Docker)

When using Laravel Sail:

```bash
./vendor/bin/sail artisan scrape "https://www.amazon.com/dp/B08N5WRWNW" --store
```

Or with the Makefile:

```bash
make artisan t='scrape "https://www.amazon.com/dp/B08N5WRWNW" --store'
```

## Supported Platforms

### Amazon
Supported domains:
- amazon.com (USA)
- amazon.co.uk (UK)
- amazon.de (Germany)
- amazon.fr (France)
- amazon.ca (Canada)
- amazon.eg (Egypt)
- And other Amazon regional sites

Platform ID extracted: **ASIN** (10-character alphanumeric)

### Jumia
Supported domains:
- jumia.com.eg (Egypt)
- jumia.co.ke (Kenya)
- jumia.com.ng (Nigeria)
- And other Jumia regional sites

Platform ID extracted: **SKU** (from URL path)

## Error Handling

### Invalid URL
```bash
php artisan scrape "https://invalid-site.com/product"
```

**Output:**
```
❌ Error: Could not detect platform from URL. Supported platforms: Amazon and Jumia
```

### Scraping Failed
```bash
php artisan scrape "https://www.amazon.com/dp/INVALID"
```

**Output:**
```
❌ Scraping failed: All 3 scraping attempts failed for URL '...'
```

### Storage Failed
If storage fails, the error will be displayed:
```
❌ Storage failed: [error message]
```

## Exit Codes

- `0`: Success
- `1`: Failure (scraping failed, storage failed, or other error)

## Advanced Usage

### Piping JSON to File

```bash
php artisan scrape "https://www.amazon.com/dp/B08N5WRWNW" --json > product.json
```

### Batch Scraping with Shell Script

```bash
#!/bin/bash
urls=(
    "https://www.amazon.com/dp/B08N5WRWNW"
    "https://www.amazon.com/dp/B07HGJQ89D"
    "https://www.jumia.com.eg/product-1.html"
)

for url in "${urls[@]}"; do
    php artisan scrape "$url" --store --quiet
done
```

### Integration with Cron Jobs

Add to crontab to periodically scrape products:

```bash
# Scrape products every hour
0 * * * * cd /path/to/app && php artisan scrape "URL" --store --quiet
```

## Data Fields Extracted

The command extracts and displays the following fields:

### Required Fields
- **Title**: Product name/title
- **Price**: Numeric price value
- **Currency**: ISO 4217 currency code

### Optional Fields
- **Platform ID**: ASIN (Amazon) or SKU (Jumia)
- **Category**: Product category
- **Rating**: Customer rating (0-5)
- **Rating Count**: Number of ratings
- **Image URL**: Main product image URL
- **Platform Category**: Original category from platform

## Database Storage

When using `--store`, the product is saved to the `products` table with:

- Auto-generated unique ID
- Scraped product data
- Platform information
- Timestamps (created_at, updated_at)
- Scraping metadata (scrape_count, last_scraped_at)
- Active status (default: true)

### Update Behavior

If a product with the same URL and platform exists:
- Without `--update`: Creates a new entry (may cause duplicate URL error)
- With `--update`: Updates the existing product with new data

## Troubleshooting

### Proxy Issues
If scraping fails due to blocking, check proxy configuration:

```bash
php artisan scrape:proxy  # Test proxy service
```

### Platform Detection
Ensure URL contains recognizable platform domain:
- ✅ `amazon.com`, `amazon.co.uk`
- ✅ `jumia.com.eg`, `jumia.co.ke`
- ❌ `amzn.to` (shortened URLs not supported)

### Debugging

Enable verbose output:

```bash
php artisan scrape "URL" -vvv
```

This will show detailed error traces and debugging information.

## Performance Considerations

- Each scrape makes HTTP requests (3 attempts with retry)
- Proxy rotation is automatic
- User-agent rotation is automatic
- Consider rate limiting for batch operations
- Use `--quiet` flag for cron jobs to reduce log noise

## Related Commands

- `php artisan scrape:test`: Test the scraping system
- `php artisan scrape:proxy`: Test proxy service
- `php artisan scrape:facades`: Test scraping facades
