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
 * Amazon Product Scraper
 * 
 * Platform-specific scraper for Amazon products.
 * Implements the driver pattern for platform-specific scraping logic.
 * 
 * Requirements Implemented:
 * - REQ-SCRAPE-003: Service SHALL support scraping from Amazon platforms
 * - REQ-SCRAPE-004: Service SHALL extract product information
 * - REQ-SCRAPE-012: Service SHALL differentiate between platform structures
 * - REQ-SCRAPE-013: Service SHALL handle platform-specific HTML parsing
 * - REQ-SCRAPE-014: Service SHALL extract platform-specific category information
 * - REQ-SCRAPE-015: Service SHALL detect and extract currency from price
 */
class AmazonScraper implements PlatformScraperInterface
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
     * Initialize CSS selectors for Amazon product data extraction
     */
    private function initializeSelectors(): void
    {
        $this->selectors = [
            'title' => [
                '#productTitle',
                '.product_title',
                'h1.a-size-large',
                'h1 span',
                '.pdp-product-name h1',
            ],
            'price' => [
                '.a-price-current .a-offscreen',
                '.a-price .a-offscreen',
                'span.a-price-symbol + span.a-price-whole',
                '.a-price-range .a-price .a-offscreen',
                '.pricePerUnit',
                '.a-price-current',
                '#corePrice_feature_div .a-price .a-offscreen',
                '.a-box-group .a-price .a-offscreen',
            ],
            'rating' => [
                '.a-icon-alt',
                '[data-hook="average-star-rating"] .a-icon-alt',
                '.acrPopover .a-icon-alt',
                '#acrPopover .a-icon-alt',
                '.cr-original-review-text .a-icon-alt',
            ],
            'rating_count' => [
                '[data-hook="total-review-count"]',
                '#acrCustomerReviewText',
                '.a-link-normal span[aria-label]',
                '.totalReviewCount',
                'a[href*="#customerReviews"] span',
            ],
            'image' => [
                '#landingImage',
                '#imgBlkFront',
                '.a-dynamic-image',
                '#main-image img',
                '.image-wrapper img',
                '.product-image img',
            ],
            'category' => [
                '#wayfinding-breadcrumbs_feature_div li:last-child span',
                '.a-breadcrumb .a-list-item:last-child a',
                '[data-testid="breadcrumb-list"] li:last-child',
                '#SalesRank .a-list-item:first-child',
                '.nav-progressive-attribute',
            ],
        ];
    }

    /**
     * Scrape product data from Amazon URL
     * 
     * @param ProductUrl $url
     * @return ScrapedProductData
     * @throws ScrapingException
     */
    public function scrape(ProductUrl $url): ScrapedProductData
    {
        Log::info('[AMAZON-SCRAPER] Starting scrape', ['url' => $url->toString()]);

        $maxAttempts = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $content = $this->fetchContent($url, $attempt);
                $crawler = new Crawler($content);
                
                $scrapedData = $this->extractProductData($crawler, $url);
                
                Log::info('[AMAZON-SCRAPER] Successfully scraped product', [
                    'url' => $url->toString(),
                    'title' => $scrapedData->getTitle(),
                    'price' => $scrapedData->getPrice()->toFloat(),
                    'attempt' => $attempt,
                ]);

                return $scrapedData;

            } catch (Exception $e) {
                $lastException = $e;
                
                Log::warning('[AMAZON-SCRAPER] Scraping attempt failed', [
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
     * Fetch HTML content from Amazon URL
     */
    private function fetchContent(ProductUrl $url, int $attempt): string
    {
        Log::debug('[AMAZON-SCRAPER] Fetching content', [
            'url' => $url->toString(),
            'attempt' => $attempt,
            'proxy_enabled' => $this->isProxyEnabled(),
        ]);

        // Get HTTP client options with or without proxy based on configuration
        $clientOptions = $this->getHttpClientOptions([
            'headers' => [
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Cache-Control' => 'max-age=0',
            ]
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

        // Check for Amazon CAPTCHA or blocking
        if (str_contains($content, 'Type the characters you see in this image') ||
            str_contains($content, 'Enter the characters you see below') ||
            str_contains($content, 'Robot Check')) {
            throw ScrapingException::blocked($url->toString(), 'Amazon CAPTCHA detected');
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
            $priceCurrency = $this->extractPriceCurrency($crawler);
            $rating = $this->extractRating($crawler);
            $ratingCount = $this->extractRatingCount($crawler);
            $imageUrl = $this->extractImageUrl($crawler);
            $category = $this->extractCategory($crawler);
            $platformId = $this->extractPlatformId($url, $crawler);

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
    private function extractPriceCurrency(Crawler $crawler): string
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
        $domain = $crawler->getUri() ?: '';
        return $this->getCurrencyFromDomain($domain);
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
                    $ratingText = $element->attr('alt') ?: $element->text();
                    $rating = $this->parseRating($ratingText);
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
                    $imageUrl = $element->attr('src') ?: $element->attr('data-src');
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
     * Extract platform-specific product identifier (ASIN for Amazon)
     * 
     * Amazon ASIN (Amazon Standard Identification Number) is a 10-character alphanumeric code
     * that uniquely identifies products on Amazon.
     * 
     * Common URL patterns:
     * - https://www.amazon.com/dp/B08N5WRWNW
     * - https://www.amazon.com/product-name/dp/B08N5WRWNW
     * - https://www.amazon.com/gp/product/B08N5WRWNW
     * 
     * @param ProductUrl $url
     * @param Crawler $crawler
     * @return string|null
     */
    private function extractPlatformId(ProductUrl $url, Crawler $crawler): ?string
    {
        $urlString = $url->toString();
        
        // Try to extract ASIN from URL patterns
        // Pattern 1: /dp/ASIN or /product/ASIN
        if (preg_match('/\/(?:dp|product|gp\/product)\/([A-Z0-9]{10})/', $urlString, $matches)) {
            return $matches[1];
        }
        
        // Pattern 2: ASIN as query parameter (?asin=...)
        if (preg_match('/[?&]asin=([A-Z0-9]{10})/i', $urlString, $matches)) {
            return strtoupper($matches[1]);
        }
        
        // Pattern 3: Try to extract from HTML meta tags or data attributes
        try {
            // Check for ASIN in meta tags
            $metaAsin = $crawler->filter('input[name="ASIN"]')->first();
            if ($metaAsin->count() > 0) {
                $asin = $metaAsin->attr('value');
                if (!empty($asin) && preg_match('/^[A-Z0-9]{10}$/', $asin)) {
                    return $asin;
                }
            }
            
            // Check for data-asin attribute
            $dataAsin = $crawler->filter('[data-asin]')->first();
            if ($dataAsin->count() > 0) {
                $asin = $dataAsin->attr('data-asin');
                if (!empty($asin) && preg_match('/^[A-Z0-9]{10}$/', $asin)) {
                    return $asin;
                }
            }
        } catch (Exception $e) {
            // Continue if HTML extraction fails
        }
        
        Log::debug('[AMAZON-SCRAPER] Could not extract ASIN', ['url' => $urlString]);
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
        
        // Remove common Amazon title suffixes
        $title = preg_replace('/\s*\([^)]*\)\s*$/', '', $title);
        
        return $title;
    }

    /**
     * Parse price from text
     */
    private function parsePrice(string $priceText): Price
    {
        // Remove currency symbols and extract numbers
        $cleanPrice = preg_replace('/[^\d.,]/', '', $priceText);
        $cleanPrice = str_replace(',', '', $cleanPrice);
        
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
            '$' => 'USD',
            '£' => 'GBP',
            '€' => 'EUR',
            'EGP' => 'EGP',
            'LE' => 'EGP',
            'جنيه' => 'EGP',
        ];

        foreach ($currencyMap as $symbol => $currency) {
            if (str_contains($priceText, $symbol)) {
                return $currency;
            }
        }

        return null;
    }

    /**
     * Get currency based on Amazon domain
     */
    private function getCurrencyFromDomain(string $domain): string
    {
        $domainCurrencyMap = [
            'amazon.com' => 'USD',
            'amazon.co.uk' => 'GBP',
            'amazon.de' => 'EUR',
            'amazon.fr' => 'EUR',
            'amazon.ca' => 'CAD',
            'amazon.eg' => 'EGP',
        ];

        foreach ($domainCurrencyMap as $domainPattern => $currency) {
            if (str_contains($domain, $domainPattern)) {
                return $currency;
            }
        }

        return 'USD'; // Default currency
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
            return 'https://amazon.com' . $imageUrl;
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
        
        // Remove common prefixes/suffixes
        $category = preg_replace('/^(in\s+)?/i', '', $category);
        $category = preg_replace('/(\s+›.*)?$/', '', $category);
        
        return $category;
    }



    /**
     * Get the platform name this scraper handles
     */
    public function getPlatformName(): string
    {
        return 'amazon';
    }
}