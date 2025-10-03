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
        $proxyConfig = $this->getProxyConfig();
        
        Log::debug('[JUMIA-SCRAPER] Fetching content', [
            'url' => $url->toString(),
            'attempt' => $attempt,
            'proxy' => $proxyConfig ? $proxyConfig['proxy'] : 'none',
        ]);

        $client = $this->httpClient->timeout(30);

        if ($proxyConfig) {
            $client = $client->withOptions($proxyConfig);
        }

        $response = $client->withHeaders([
            'User-Agent' => $this->getRandomUserAgent(),
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9,ar;q=0.8',
            'Accept-Encoding' => 'gzip, deflate',
            'DNT' => '1',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Cache-Control' => 'max-age=0',
        ])->get($url->toString());

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

        // Check for Jumia blocking or errors
        if (str_contains($content, 'Access denied') ||
            str_contains($content, 'Blocked') ||
            str_contains($content, 'Too Many Requests')) {
            throw ScrapingException::blocked($url->toString(), 'Jumia access denied');
        }

        return $content;
    }

    /**
     * Extract product data from HTML content
     */
    private function extractProductData(Crawler $crawler, ProductUrl $url): ScrapedProductData
    {
        try {
            $title = $this->extractTitle($crawler);
            $price = $this->extractPrice($crawler);
            $priceCurrency = $this->extractPriceCurrency($crawler, $url);
            $rating = $this->extractRating($crawler);
            $ratingCount = $this->extractRatingCount($crawler);
            $imageUrl = $this->extractImageUrl($crawler);
            $category = $this->extractCategory($crawler);

            return new ScrapedProductData(
                title: $title,
                price: $price,
                priceCurrency: $priceCurrency,
                rating: $rating,
                ratingCount: $ratingCount,
                platformCategory: $category,
                imageUrl: $imageUrl
            );

        } catch (Exception $e) {
            throw ScrapingException::dataExtractionFailed(
                $url->toString(),
                'Failed to extract product data: ' . $e->getMessage()
            );
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
        foreach ($this->selectors['image'] as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count() > 0) {
                    $imageUrl = $element->attr('data-src') ?: 
                               $element->attr('src') ?: 
                               $element->attr('data-lazy-src');
                    
                    if (!empty($imageUrl)) {
                        return $this->normalizeImageUrl($imageUrl);
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

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
     */
    private function getCurrencyFromDomain(string $url): string
    {
        $domainCurrencyMap = [
            'jumia.com.eg' => 'EGP',
            'jumia.co.ke' => 'KES',
            'jumia.com.ng' => 'NGN',
            'jumia.com' => 'USD',
        ];

        foreach ($domainCurrencyMap as $domain => $currency) {
            if (str_contains($url, $domain)) {
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
     * Get random user agent for Jumia
     */
    private function getRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
        ];

        return $userAgents[array_rand($userAgents)];
    }

    /**
     * Get the platform name this scraper handles
     */
    public function getPlatformName(): string
    {
        return 'jumia';
    }
}