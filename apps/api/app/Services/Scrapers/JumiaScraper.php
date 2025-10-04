<?php

declare(strict_types=1);

namespace App\Services\Scrapers;

use App\Traits\UsesProxy;
use Domain\Product\Exception\ScrapingException;
use Domain\Product\Service\PlatformScraperInterface;
use Domain\Product\Service\ScrapedProductData;
use Domain\Product\ValueObject\Price;
use Domain\Product\ValueObject\ProductUrl;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Exception;

/**
 * Jumia Product Scraper
 * 
 * Platform-specific scraper for Jumia products.
 * Implements the driver pattern for platform-specific scraping logic.
 * 
 * Requirements Implemented:
 * - REQ-SCRAPE-003: Service SHALL support scraping from Jumia platforms
 * - REQ-SCRAPE-004: Service SHALL extract product information
 * - REQ-SCRAPE-012: Service SHALL differentiate between platform structures
 * - REQ-SCRAPE-013: Service SHALL handle platform-specific HTML parsing
 * - REQ-SCRAPE-014: Service SHALL extract platform-specific category information
 * - REQ-SCRAPE-015: Service SHALL detect and extract currency from price
 */
class JumiaScraper implements PlatformScraperInterface
{
    use UsesProxy;

    private HttpClient $httpClient;
    private array $selectors;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->initializeSelectors();
    }

    /**
     * Initialize CSS selectors for Jumia product data extraction
     */
    private function initializeSelectors(): void
    {
        $this->selectors = [
            'title' => [
                '[data-testid="pdp-product-name"] h1',
                '.pdp-product-name h1',
                'h1.-fs20.-pts.-pbxs',
                '.pdp-title h1',
                '.pdp-product-title',
                'h1.title',
            ],
            'price' => [
                '.price .notranslate',
                '.current-price .notranslate',
                '.-tal .notranslate',
                '.price-box .price',
                '.price-current',
                '.-b.-ltr.-tal.-fs20.-prxs span',
            ],
            'rating' => [
                '.stars._s',
                '[data-testid="star-rating"]',
                '.rating-stars .stars',
                '.review-stars .stars',
                '.star-rating',
            ],
            'rating_count' => [
                '.rating-label a',
                '[data-testid="review-count"]',
                '.rating-count',
                '.review-count',
                '.total-reviews',
            ],
            'image' => [
                'img[data-lazy-slide="true"]',  // Primary product carousel images
                '.sldr._img._prod img[data-src]',  // Product slider images
                'img[data-lazy="true"][data-src]',  // Lazy-loaded images
                '.pdp-gallery img[data-src]',
                '.gallery-image img',
                '.product-gallery img',
                '.image-viewer img',
                '.pdp-image img',
                '.main-image img',
            ],
            'category' => [
                '.-pvs .breadcrumb li:last-child',
                '.breadcrumb .breadcrumb-item:last-child',
                '.category-breadcrumb li:last-child',
                '.pdp-breadcrumb li:last-child',
                '.navigation-breadcrumb .last',
            ],
        ];
    }

    /**
     * Scrape product data from Jumia URL
     * 
     * @param ProductUrl $url
     * @return ScrapedProductData
     * @throws ScrapingException
     */
    public function scrape(ProductUrl $url): ScrapedProductData
    {
        Log::info('[JUMIA-SCRAPER] Starting scrape', ['url' => $url->toString()]);

        $maxAttempts = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $content = $this->fetchContent($url, $attempt);
                $crawler = new Crawler($content);
                
                $scrapedData = $this->extractProductData($crawler, $url);
                
                Log::info('[JUMIA-SCRAPER] Successfully scraped product', [
                    'url' => $url->toString(),
                    'title' => $scrapedData->getTitle(),
                    'price' => $scrapedData->getPrice()->toFloat(),
                    'attempt' => $attempt,
                ]);

                return $scrapedData;

            } catch (Exception $e) {
                $lastException = $e;
                
                Log::warning('[JUMIA-SCRAPER] Scraping attempt failed', [
                    'url' => $url->toString(),
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxAttempts) {
                    // Rotate proxy for next attempt
                    $this->rotateProxy();
                    sleep(2); // Wait before retry
                }
            }
        }

        throw ScrapingException::allAttemptsFailed(
            $url->toString(),
            $maxAttempts,
            $lastException?->getMessage() ?? 'Unknown error'
        );
    }

    /**
     * Fetch HTML content from Jumia URL
     */
    private function fetchContent(ProductUrl $url, int $attempt): string
    {
        Log::debug('[JUMIA-SCRAPER] Fetching content', [
            'url' => $url->toString(),
            'attempt' => $attempt,
            'proxy_enabled' => $this->isProxyEnabled(),
        ]);

        // Get HTTP client options with or without proxy based on configuration
        $clientOptions = $this->getHttpClientOptions([
            'headers' => [
                'Accept-Language' => 'en-GB,en;q=0.9,en-US;q=0.8,ar;q=0.7',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Referer' => 'https://www.jumia.com.eg/?srsltid=0',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Cache-Control' => 'max-age=0',
                'Upgrade-Insecure-Requests' => '1',
            ],
            'allow_redirects' => true,
            'http_errors' => false, // Don't throw on 4xx/5xx
        ]);

        $response = $this->httpClient->withOptions($clientOptions)->get($url->toString());

        if (!$response->successful()) {
            throw ScrapingException::httpError(
                $url->toString(),
                $response->status(),
                $response->body()
            );
        }

        $content = $response->body();
        
        if (empty($content)) {
            throw ScrapingException::emptyResponse($url->toString());
        }

        // Check for Jumia blocking or errors - look for more specific patterns
        if (str_contains($content, 'Access denied') ||
            str_contains($content, 'Blocked') ||
            str_contains($content, 'Enable JavaScript and cookies') ||
            str_contains($content, 'cf-browser-verification') ||
            str_contains($content, 'Just a moment...')) {
            
            Log::warning('[JUMIA-SCRAPER] Cloudflare challenge detected', [
                'url' => $url->toString(),
                'attempt' => $attempt,
                'content_preview' => substr($content, 0, 500),
            ]);
            
            throw ScrapingException::blocked($url->toString(), 'Cloudflare protection detected - may require proxy or browser automation');
        }

        // Debug: Log a sample of the HTML to help troubleshoot selector issues
        Log::debug('[JUMIA-SCRAPER] HTML sample received', [
            'content_length' => strlen($content),
            'has_price_class' => str_contains($content, '-prc'),
            'has_h1_tag' => str_contains($content, '<h1'),
            'has_json_ld' => str_contains($content, 'application/ld+json'),
            'has_react_root' => str_contains($content, 'id="__NEXT_DATA__"') || str_contains($content, 'id="root"'),
            'title_tag' => preg_match('/<title>(.*?)<\/title>/i', $content, $matches) ? $matches[1] : 'not found',
        ]);

        return $content;
    }

    /**
     * Extract product data from HTML content
     */
    private function extractProductData(Crawler $crawler, ProductUrl $url): ScrapedProductData
    {
        try {
            // Try to extract from JSON-LD first (more reliable)
            $jsonLdData = $this->extractJsonLd($crawler);
            
            if ($jsonLdData) {
                Log::debug('[JUMIA-SCRAPER] Extracting from JSON-LD', ['data_keys' => array_keys($jsonLdData)]);
                
                $title = $jsonLdData['name'] ?? $this->extractTitle($crawler);
                
                // Handle price - JSON-LD provides it as a float
                if (isset($jsonLdData['offers']['price'])) {
                    $priceValue = (float) $jsonLdData['offers']['price'];
                    $priceCurrency = $jsonLdData['offers']['priceCurrency'] ?? $this->extractPriceCurrency($crawler, $url);
                    $price = Price::fromFloat($priceValue, $priceCurrency);
                } else {
                    $price = $this->extractPrice($crawler);
                    $priceCurrency = $this->extractPriceCurrency($crawler, $url);
                }
                
                $rating = isset($jsonLdData['aggregateRating']['ratingValue']) ? (float) $jsonLdData['aggregateRating']['ratingValue'] : $this->extractRating($crawler);
                $ratingCount = isset($jsonLdData['aggregateRating']['reviewCount']) ? (int) $jsonLdData['aggregateRating']['reviewCount'] : $this->extractRatingCount($crawler);
                
                // Handle image - can be array or string
                if (isset($jsonLdData['image'])) {
                    $imageUrl = is_array($jsonLdData['image']) ? ($jsonLdData['image'][0] ?? null) : $jsonLdData['image'];
                    
                    Log::debug('[JUMIA-SCRAPER] Image extracted from JSON-LD', [
                        'image_type' => is_array($jsonLdData['image']) ? 'array' : 'string',
                        'image_url' => $imageUrl,
                    ]);
                    
                    // If JSON-LD image is empty, try HTML selectors
                    if (empty($imageUrl)) {
                        $imageUrl = $this->extractImageUrl($crawler);
                        Log::debug('[JUMIA-SCRAPER] JSON-LD image empty, falling back to HTML', [
                            'image_url' => $imageUrl,
                        ]);
                    }
                } else {
                    $imageUrl = $this->extractImageUrl($crawler);
                    
                    Log::debug('[JUMIA-SCRAPER] Image extracted from HTML', [
                        'image_url' => $imageUrl,
                    ]);
                }
                
                $category = !empty($jsonLdData['category']) ? $jsonLdData['category'] : $this->extractCategory($crawler);
                $platformId = isset($jsonLdData['sku']) ? (string) $jsonLdData['sku'] : $this->extractPlatformId($url, $crawler);
                
            } else {
                // Fallback to HTML selectors
                Log::debug('[JUMIA-SCRAPER] JSON-LD not found, falling back to HTML selectors');
                
                $title = $this->extractTitle($crawler);
                $price = $this->extractPrice($crawler);
                $priceCurrency = $this->extractPriceCurrency($crawler, $url);
                $rating = $this->extractRating($crawler);
                $ratingCount = $this->extractRatingCount($crawler);
                $imageUrl = $this->extractImageUrl($crawler);
                $category = $this->extractCategory($crawler);
                $platformId = $this->extractPlatformId($url, $crawler);
            }

            return new ScrapedProductData(
                title: $title,
                price: $price,
                priceCurrency: $priceCurrency,
                rating: $rating,
                ratingCount: $ratingCount,
                platformCategory: $category,
                imageUrl: $imageUrl,
                platformId: $platformId
            );

        } catch (Exception $e) {
            throw ScrapingException::dataExtractionFailed(
                $url->toString(),
                'Failed to extract product data: ' . $e->getMessage()
            );
        }
    }

    /**
     * Extract JSON-LD structured data
     */
    private function extractJsonLd(Crawler $crawler): ?array
    {
        try {
            $jsonLdElements = $crawler->filter('script[type="application/ld+json"]');
            
            foreach ($jsonLdElements as $element) {
                $jsonContent = $element->textContent;
                $data = json_decode($jsonContent, true);
                
                if ($data && isset($data['@type']) && $data['@type'] === 'Product') {
                    return $data;
                }
                
                // Sometimes it's wrapped in an array
                if ($data && is_array($data)) {
                    foreach ($data as $item) {
                        if (isset($item['@type']) && $item['@type'] === 'Product') {
                            return $item;
                        }
                    }
                }
            }
            
            return null;
        } catch (Exception $e) {
            Log::warning('[JUMIA-SCRAPER] Failed to parse JSON-LD', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract product title
     */
    private function extractTitle(Crawler $crawler): string
    {
        foreach ($this->selectors['title'] as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $title = trim($element->text());
                    if (!empty($title)) {
                        return $this->cleanTitle($title);
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        throw new Exception('Could not extract product title');
    }

    /**
     * Extract product price
     */
    private function extractPrice(Crawler $crawler): Price
    {
        foreach ($this->selectors['price'] as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $priceText = trim($element->text());
                    if (!empty($priceText)) {
                        return $this->parsePrice($priceText);
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        throw new Exception('Could not extract product price');
    }

    /**
     * Extract price currency
     */
    private function extractPriceCurrency(Crawler $crawler, ProductUrl $url): string
    {
        // Try to extract currency from price elements
        foreach ($this->selectors['price'] as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $priceText = trim($element->text());
                    $currency = $this->extractCurrencyFromText($priceText);
                    if ($currency) {
                        return $currency;
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        // Try to determine currency from URL domain
        return $this->getCurrencyFromDomain($url->toString());
    }

    /**
     * Extract product rating
     */
    private function extractRating(Crawler $crawler): ?float
    {
        foreach ($this->selectors['rating'] as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    // Try to get rating from class names or attributes
                    $class = $element->attr('class') ?: '';
                    $rating = $this->parseRatingFromClass($class);
                    
                    if ($rating === null) {
                        $ratingText = $element->text();
                        $rating = $this->parseRating($ratingText);
                    }
                    
                    if ($rating !== null) {
                        return $rating;
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Extract rating count
     */
    private function extractRatingCount(Crawler $crawler): int
    {
        foreach ($this->selectors['rating_count'] as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $countText = $element->text();
                    $count = $this->parseRatingCount($countText);
                    if ($count > 0) {
                        return $count;
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return 0;
    }

    /**
     * Extract product image URL
     */
    private function extractImageUrl(Crawler $crawler): ?string
    {
        Log::debug('[JUMIA-SCRAPER] Attempting to extract image URL from HTML');
        
        foreach ($this->selectors['image'] as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $imageUrl = $element->attr('data-src') ?: 
                               $element->attr('src') ?: 
                               $element->attr('data-lazy-src');
                    
                    Log::debug('[JUMIA-SCRAPER] Image selector tried', [
                        'selector' => $selector,
                        'found' => !empty($imageUrl),
                        'url_preview' => !empty($imageUrl) ? substr($imageUrl, 0, 100) : null,
                    ]);
                    
                    if (!empty($imageUrl)) {
                        return $this->normalizeImageUrl($imageUrl);
                    }
                }
            } catch (Exception $e) {
                Log::debug('[JUMIA-SCRAPER] Image selector failed', [
                    'selector' => $selector,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        Log::warning('[JUMIA-SCRAPER] No image URL found with any selector');
        return null;
    }

    /**
     * Extract product category
     */
    private function extractCategory(Crawler $crawler): ?string
    {
        foreach ($this->selectors['category'] as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $category = trim($element->text());
                    if (!empty($category)) {
                        return $this->cleanCategory($category);
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Extract platform-specific product identifier (SKU for Jumia)
     * 
     * Jumia uses SKUs embedded in URLs to uniquely identify products.
     * 
     * Common URL patterns:
     * - https://www.jumia.com.eg/product-name-sku.html
     * - https://www.jumia.co.ke/category/product-name-sku.html
     * 
     * The SKU is typically the last segment before .html
     * 
     * @param ProductUrl $url
     * @param Crawler $crawler
     * @return string|null
     */
    private function extractPlatformId(ProductUrl $url, Crawler $crawler): ?string
    {
        $urlString = $url->toString();
        
        // Pattern 1: Extract SKU from URL path (usually the last segment before .html)
        // Example: /product-name-ABC123XYZ.html -> ABC123XYZ
        if (preg_match('/\/([A-Z0-9_-]{5,})\.html/i', $urlString, $matches)) {
            // The matched segment might include product slug, get the last part
            $segment = $matches[1];
            
            // Try to extract the actual SKU (usually alphanumeric at the end)
            if (preg_match('/([A-Z0-9_-]{5,})$/i', $segment, $skuMatch)) {
                return strtoupper($skuMatch[1]);
            }
            
            return strtoupper($segment);
        }
        
        // Pattern 2: Try to extract from SKU query parameter
        if (preg_match('/[?&]sku=([A-Z0-9_-]+)/i', $urlString, $matches)) {
            return strtoupper($matches[1]);
        }
        
        // Pattern 3: Try to extract from HTML data attributes
        try {
            // Check for SKU in data attributes
            $dataSku = $crawler->filter('[data-sku]')->first();
            if ($dataSku->count() > 0) {
                $sku = $dataSku->attr('data-sku');
                if (!empty($sku)) {
                    return strtoupper($sku);
                }
            }
            
            // Check for SKU in meta tags
            $metaSku = $crawler->filter('meta[name="product:sku"]')->first();
            if ($metaSku->count() > 0) {
                $sku = $metaSku->attr('content');
                if (!empty($sku)) {
                    return strtoupper($sku);
                }
            }
            
            // Check for product ID in data-id or data-product-id
            $dataProductId = $crawler->filter('[data-product-id], [data-id]')->first();
            if ($dataProductId->count() > 0) {
                $productId = $dataProductId->attr('data-product-id') ?: $dataProductId->attr('data-id');
                if (!empty($productId)) {
                    return strtoupper($productId);
                }
            }
        } catch (Exception $e) {
            // Continue if HTML extraction fails
        }
        
        Log::debug('[JUMIA-SCRAPER] Could not extract SKU', ['url' => $urlString]);
        return null;
    }

    /**
     * Clean product title
     */
    private function cleanTitle(string $title): string
    {
        // Remove extra whitespace and newlines
        $title = preg_replace('/\s+/', ' ', $title);
        $title = trim($title);
        
        return $title;
    }

    /**
     * Parse price from text
     */
    private function parsePrice(string $priceText): Price
    {
        // Remove currency symbols and non-numeric characters except dots and commas
        $cleanPrice = preg_replace('/[^\d.,]/', '', $priceText);
        
        // Handle different decimal separators
        if (str_contains($cleanPrice, ',') && str_contains($cleanPrice, '.')) {
            // Assume comma is thousands separator and dot is decimal
            $cleanPrice = str_replace(',', '', $cleanPrice);
        } elseif (str_contains($cleanPrice, ',')) {
            // Check if comma is decimal separator (European style)
            $parts = explode(',', $cleanPrice);
            if (count($parts) === 2 && strlen(end($parts)) <= 2) {
                $cleanPrice = str_replace(',', '.', $cleanPrice);
            } else {
                $cleanPrice = str_replace(',', '', $cleanPrice);
            }
        }
        
        if (!is_numeric($cleanPrice)) {
            throw new Exception("Could not parse price: {$priceText}");
        }

        return Price::fromFloat((float) $cleanPrice);
    }

    /**
     * Extract currency from price text
     */
    private function extractCurrencyFromText(string $priceText): ?string
    {
        $currencyMap = [
            'EGP' => 'EGP',
            'LE' => 'EGP',
            'جنيه' => 'EGP',
            'KSh' => 'KES',
            'Ksh' => 'KES',
            '₦' => 'NGN',
            'NGN' => 'NGN',
            '$' => 'USD',
            '£' => 'GBP',
            '€' => 'EUR',
        ];

        foreach ($currencyMap as $symbol => $currency) {
            if (str_contains($priceText, $symbol)) {
                return $currency;
            }
        }

        return null;
    }

    /**
     * Get currency based on Jumia domain
     * 
     * REQ-SCRAPE-015: Service SHALL detect and extract currency from price
     */
    private function getCurrencyFromDomain(string $url): string
    {
        // Parse the domain from the URL
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        
        // Remove 'www.' prefix if present
        $host = preg_replace('/^www\./', '', $host);
        
        // Map domains to currency codes
        $domainCurrencyMap = [
            'jumia.com.eg' => 'EGP',  // Egypt
            'jumia.com.ng' => 'NGN',  // Nigeria
            'jumia.co.ke' => 'KES',   // Kenya
            'jumia.ma' => 'MAD',      // Morocco
            'jumia.ci' => 'XOF',      // Ivory Coast (CFA Franc)
            'jumia.sn' => 'XOF',      // Senegal (CFA Franc)
            'jumia.ug' => 'UGX',      // Uganda
            'jumia.co.za' => 'ZAR',   // South Africa
            'jumia.com.tn' => 'TND',  // Tunisia
            'jumia.dz' => 'DZD',      // Algeria
            'jumia.com.gh' => 'GHS',  // Ghana
            'jumia.com' => 'USD',     // Generic fallback
        ];

        // Exact domain match
        if (isset($domainCurrencyMap[$host])) {
            return $domainCurrencyMap[$host];
        }

        // Fallback: Check if any domain is contained in the host
        foreach ($domainCurrencyMap as $domain => $currency) {
            if ($host === $domain) {
                return $currency;
            }
        }

        return 'EGP'; // Default for Egypt
    }

    /**
     * Parse rating from class names (Jumia uses CSS classes for stars)
     */
    private function parseRatingFromClass(string $class): ?float
    {
        if (preg_match('/stars\s+_s(\d+)/', $class, $matches)) {
            return (float) $matches[1];
        }

        if (preg_match('/rating-(\d)/', $class, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    /**
     * Parse rating from text
     */
    private function parseRating(string $ratingText): ?float
    {
        if (preg_match('/(\d+\.?\d*)\s*out\s*of\s*5/i', $ratingText, $matches)) {
            return (float) $matches[1];
        }

        if (preg_match('/(\d+\.?\d*)\s*\/\s*5/', $ratingText, $matches)) {
            return (float) $matches[1];
        }

        if (preg_match('/(\d+\.?\d*)/', $ratingText, $matches)) {
            $rating = (float) $matches[1];
            return $rating <= 5 ? $rating : null;
        }

        return null;
    }

    /**
     * Parse rating count from text
     */
    private function parseRatingCount(string $countText): int
    {
        // Remove commas and extract numbers
        $numbers = preg_replace('/[^\d]/', '', $countText);
        
        if (is_numeric($numbers)) {
            return (int) $numbers;
        }

        return 0;
    }

    /**
     * Normalize image URL
     */
    private function normalizeImageUrl(string $imageUrl): string
    {
        // Convert relative URLs to absolute
        if (str_starts_with($imageUrl, '//')) {
            return 'https:' . $imageUrl;
        }

        if (str_starts_with($imageUrl, '/')) {
            return 'https://jumia.com.eg' . $imageUrl;
        }

        return $imageUrl;
    }

    /**
     * Clean category text
     */
    private function cleanCategory(string $category): string
    {
        $category = trim($category);
        $category = preg_replace('/\s+/', ' ', $category);
        
        return $category;
    }



    /**
     * Get the platform name this scraper handles
     */
    public function getPlatformName(): string
    {
        return 'jumia';
    }
}