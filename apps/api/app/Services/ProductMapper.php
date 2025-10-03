<?php

declare(strict_types=1);

namespace App\Services;

use Domain\Product\Exception\MappingException;
use Domain\Product\Service\ProductMapperInterface;
use Domain\Product\Service\ScrapedProductData;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\Price;
use Domain\Product\ValueObject\ProductUrl;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Product Mapper Service
 * 
 * Maps scraped product data utilizing domain business logic.
 * This service transforms raw scraped data into properly structured arrays
 * according to business rules and validation requirements.
 * 
 * Requirements Implemented:
 * - REQ-MAP-001: System SHALL implement a dedicated product mapping service
 * - REQ-MAP-002: Service SHALL transform scraped data into structured format
 * - REQ-MAP-003: Service SHALL validate and normalize product data
 * - REQ-MAP-004: Service SHALL apply business rules for data transformation
 * - REQ-MAP-005: Service SHALL handle mapping errors gracefully
 * - REQ-MAP-006: Service SHALL log mapping activities and errors
 * - REQ-MAP-007: Service SHALL support batch mapping of multiple products
 * - REQ-MAP-008: Service SHALL extract and normalize pricing information
 * - REQ-MAP-009: Service SHALL categorize products based on platform and content
 * - REQ-MAP-010: Service SHALL generate unique product identifiers
 */
class ProductMapper implements ProductMapperInterface
{
    /**
     * Currency symbol mappings for normalization
     */
    private const CURRENCY_MAPPINGS = [
        '$' => 'USD',
        '€' => 'EUR',
        '£' => 'GBP',
        '₹' => 'INR',
        'KSh' => 'KES',
        'UGX' => 'UGX',
        'TZS' => 'TZS',
        'ETB' => 'ETB',
        'EGP' => 'EGP',
        'MAD' => 'MAD',
        'TND' => 'TND',
        'DZD' => 'DZD',
        'NGN' => 'NGN',
        'GHS' => 'GHS',
        'CFA' => 'XOF',
        'R' => 'ZAR',
    ];

    /**
     * Platform-specific category mappings
     */
    private const PLATFORM_CATEGORIES = [
        'amazon' => [
            'electronics' => ['Electronics', 'Computers', 'Cell Phones', 'Camera', 'TV'],
            'clothing' => ['Clothing', 'Shoes', 'Jewelry', 'Watches', 'Handbags'],
            'home' => ['Home', 'Kitchen', 'Garden', 'Tools', 'Furniture'],
            'books' => ['Books', 'Kindle', 'Audible', 'Magazines'],
            'health' => ['Health', 'Beauty', 'Personal Care', 'Sports'],
            'toys' => ['Toys', 'Games', 'Baby Products'],
            'automotive' => ['Automotive', 'Motorcycle', 'Industrial'],
        ],
        'jumia' => [
            'electronics' => ['Phones', 'Computers', 'Electronics', 'Gaming'],
            'fashion' => ['Fashion', 'Shoes', 'Bags', 'Jewelry'],
            'home' => ['Home', 'Appliances', 'Furniture', 'Garden'],
            'beauty' => ['Health & Beauty', 'Personal Care'],
            'sports' => ['Sports', 'Outdoor'],
            'baby' => ['Baby Products', 'Kids'],
            'automotive' => ['Automotive', 'Parts & Accessories'],
        ],
    ];

    /**
     * Map scraped product data to structured array
     * 
     * REQ-MAP-002: Service SHALL transform scraped data into structured format
     * REQ-MAP-003: Service SHALL validate and normalize product data
     * REQ-MAP-004: Service SHALL apply business rules for data transformation
     * 
     * @param ScrapedProductData $scrapedData
     * @param Platform $platform
     * @param ProductUrl $originalUrl
     * @return array
     * @throws MappingException
     */
    public function mapToProduct(
        ScrapedProductData $scrapedData, 
        Platform $platform, 
        ProductUrl $originalUrl
    ): array {
        Log::info('[PRODUCT-MAPPER] Starting product mapping', [
            'title' => $scrapedData->getTitle(),
            'platform' => $platform->toString(),
            'url' => $originalUrl->toString(),
        ]);

        try {
            // Generate unique product ID
            $productId = $this->generateProductId($scrapedData, $platform, $originalUrl);

            // Normalize and validate title
            $title = $this->mapTitle($scrapedData->getTitle());

            // Normalize and validate price
            $price = $this->mapPrice($scrapedData, $platform);

            // Extract and categorize product
            $category = $this->mapCategory($scrapedData, $platform);

            // Create mapped product data
            $mappedProduct = [
                'id' => $productId,
                'title' => $title,
                'price' => $price->toFloat(),
                'currency' => $scrapedData->getPriceCurrency(),
                'category' => $category,
                'platform' => $platform->toString(),
                'original_url' => $originalUrl->toString(),
                'image_url' => $scrapedData->getImageUrl(),
                'rating' => $scrapedData->getRating(),
                'rating_count' => $scrapedData->getRatingCount(),
                'platform_category' => $scrapedData->getPlatformCategory(),
                'created_at' => now()->toISOString(),
            ];

            Log::info('[PRODUCT-MAPPER] Successfully mapped product', [
                'product_id' => $productId,
                'title' => $title,
                'price' => $price->toFloat(),
                'currency' => $scrapedData->getPriceCurrency(),
                'category' => $category,
                'platform' => $platform->toString(),
            ]);

            return $mappedProduct;

        } catch (Exception $e) {
            Log::error('[PRODUCT-MAPPER] Product mapping failed', [
                'title' => $scrapedData->getTitle(),
                'platform' => $platform->toString(),
                'url' => $originalUrl->toString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw MappingException::failed($scrapedData->getTitle(), $e->getMessage());
        }
    }

    /**
     * Map multiple scraped products to domain entities
     * 
     * REQ-MAP-007: Service SHALL support batch mapping of multiple products
     * 
     * @param array $scrapedDataArray Array of ScrapedProductData objects with their metadata
     * @return array Array of mapped products or mapping exceptions
     */
    public function mapMultipleProducts(array $scrapedDataArray): array
    {
        Log::info('[PRODUCT-MAPPER] Starting batch mapping', [
            'product_count' => count($scrapedDataArray),
        ]);

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($scrapedDataArray as $index => $item) {
            $scrapedData = $item['data'];
            $platform = $item['platform'];
            $originalUrl = $item['url'];

            try {
                $product = $this->mapToProduct($scrapedData, $platform, $originalUrl);
                $results[$index] = [
                    'status' => 'success',
                    'product' => $product,
                    'original_data' => [
                        'title' => $scrapedData->getTitle(),
                        'url' => $originalUrl->toString(),
                        'platform' => $platform->toString(),
                    ],
                ];
                $successCount++;

            } catch (MappingException $e) {
                $results[$index] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'original_data' => [
                        'title' => $scrapedData->getTitle(),
                        'url' => $originalUrl->toString(),
                        'platform' => $platform->toString(),
                    ],
                ];
                $failureCount++;

                Log::warning('[PRODUCT-MAPPER] Product mapping failed in batch operation', [
                    'index' => $index,
                    'title' => $scrapedData->getTitle(),
                    'platform' => $platform->toString(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[PRODUCT-MAPPER] Batch mapping completed', [
            'total' => count($scrapedDataArray),
            'success' => $successCount,
            'failed' => $failureCount,
            'success_rate' => $successCount > 0 ? round(($successCount / count($scrapedDataArray)) * 100, 2) : 0,
        ]);

        return $results;
    }

    /**
     * Generate unique product identifier
     * 
     * REQ-MAP-010: Service SHALL generate unique product identifiers
     * 
     * @param ScrapedProductData $scrapedData
     * @param Platform $platform
     * @param ProductUrl $originalUrl
     * @return string
     */
    private function generateProductId(
        ScrapedProductData $scrapedData, 
        Platform $platform, 
        ProductUrl $originalUrl
    ): string {
        // Create a unique identifier based on URL and title
        $uniqueString = $originalUrl->toString() . '|' . $scrapedData->getTitle();
        $hash = hash('sha256', $uniqueString);
        
        // Create platform-specific ID format
        $platformPrefix = strtoupper($platform->toString());
        $shortHash = substr($hash, 0, 12);
        
        return "{$platformPrefix}_{$shortHash}";
    }

    /**
     * Map and normalize title
     * 
     * @param string $rawTitle
     * @return string
     * @throws MappingException
     */
    private function mapTitle(string $rawTitle): string
    {
        if (empty(trim($rawTitle))) {
            throw MappingException::emptyField('title');
        }

        // Normalize title
        $normalizedTitle = $this->normalizeText($rawTitle);
        
        // Validate length
        if (strlen($normalizedTitle) > 500) {
            $normalizedTitle = substr($normalizedTitle, 0, 497) . '...';
        }

        if (strlen($normalizedTitle) < 3) {
            throw MappingException::invalidField('title', 'Title too short');
        }

        return $normalizedTitle;
    }

    /**
     * Map and normalize description (not available in current ScrapedProductData)
     * 
     * @param string $rawDescription
     * @return string
     */
    private function mapDescription(string $rawDescription): string
    {
        $normalizedDescription = $this->normalizeText($rawDescription);
        
        // Truncate if too long
        if (strlen($normalizedDescription) > 2000) {
            $normalizedDescription = substr($normalizedDescription, 0, 1997) . '...';
        }

        return $normalizedDescription ?: 'No description available';
    }

    /**
     * Map and normalize price information
     * 
     * REQ-MAP-008: Service SHALL extract and normalize pricing information
     * 
     * @param ScrapedProductData $scrapedData
     * @param Platform $platform
     * @return Price
     * @throws MappingException
     */
    private function mapPrice(ScrapedProductData $scrapedData, Platform $platform): Price
    {
        $rawPrice = $scrapedData->getPrice();
        $rawCurrency = $scrapedData->getPriceCurrency();

        if ($rawPrice === null || $rawPrice->toFloat() <= 0) {
            throw MappingException::invalidField('price', 'Price must be greater than 0');
        }

        // Normalize currency
        $normalizedCurrency = $this->normalizeCurrency($rawCurrency, $platform);
        
        // Apply platform-specific price validation
        $this->validatePriceByPlatform($rawPrice->toFloat(), $platform);

        return Price::make($rawPrice->toFloat(), $normalizedCurrency);
    }

    /**
     * Map and categorize product
     * 
     * REQ-MAP-009: Service SHALL categorize products based on platform and content
     * 
     * @param ScrapedProductData $scrapedData
     * @param Platform $platform
     * @return string
     */
    private function mapCategory(ScrapedProductData $scrapedData, Platform $platform): string
    {
        $rawCategory = $scrapedData->getPlatformCategory();
        $title = $scrapedData->getTitle();

        // Try to match with platform-specific categories
        $normalizedCategory = $this->categorizeBySimilarity($rawCategory ?: '', $title, '', $platform);

        return $normalizedCategory;
    }

    /**
     * Normalize text content
     * 
     * @param string $text
     * @return string
     */
    private function normalizeText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim
        $text = trim($text);
        
        // Remove special characters that might cause issues
        $text = preg_replace('/[^\p{L}\p{N}\p{P}\p{S}\p{Z}]/u', '', $text);
        
        return $text;
    }

    /**
     * Normalize currency code
     * 
     * @param string $rawCurrency
     * @param Platform $platform
     * @return string
     */
    private function normalizeCurrency(string $rawCurrency, Platform $platform): string
    {
        // Check direct mapping
        if (isset(self::CURRENCY_MAPPINGS[$rawCurrency])) {
            return self::CURRENCY_MAPPINGS[$rawCurrency];
        }

        // Platform-specific currency defaults
        $platformDefaults = [
            'amazon' => 'USD',
            'jumia' => 'KES', // Default to Kenyan Shilling for African market
        ];

        // Try to extract currency from text
        $extractedCurrency = $this->extractCurrencyFromText($rawCurrency);
        if ($extractedCurrency) {
            return $extractedCurrency;
        }

        // Fall back to platform default
        return $platformDefaults[$platform->toString()] ?? 'USD';
    }

    /**
     * Extract currency from text
     * 
     * @param string $text
     * @return string|null
     */
    private function extractCurrencyFromText(string $text): ?string
    {
        // Common currency patterns
        $patterns = [
            '/\b(USD|EUR|GBP|KES|UGX|TZS|ETB|EGP|MAD|TND|NGN|GHS|ZAR)\b/i',
            '/\$(USD)?/i' => 'USD',
            '/€(EUR)?/i' => 'EUR',
            '/£(GBP)?/i' => 'GBP',
            '/KSh/i' => 'KES',
        ];

        foreach ($patterns as $pattern => $currency) {
            if (is_string($pattern) && preg_match($pattern, $text, $matches)) {
                return strtoupper($matches[1] ?? $currency);
            } elseif (is_numeric($pattern) && preg_match($currency, $text)) {
                return $currency;
            }
        }

        return null;
    }

    /**
     * Validate price by platform rules
     * 
     * @param float $price
     * @param Platform $platform
     * @throws MappingException
     */
    private function validatePriceByPlatform(float $price, Platform $platform): void
    {
        $platformLimits = [
            'amazon' => ['min' => 0.01, 'max' => 999999.99],
            'jumia' => ['min' => 1.00, 'max' => 9999999.99],
        ];

        $limits = $platformLimits[$platform->toString()] ?? ['min' => 0.01, 'max' => 999999.99];

        if ($price < $limits['min'] || $price > $limits['max']) {
            throw MappingException::invalidField(
                'price',
                "Price {$price} is outside valid range for {$platform->toString()}"
            );
        }
    }

    /**
     * Categorize product by similarity to known categories
     * 
     * @param string $rawCategory
     * @param string $title
     * @param string $description
     * @param Platform $platform
     * @return string
     */
    private function categorizeBySimilarity(
        string $rawCategory, 
        string $title, 
        string $description, 
        Platform $platform
    ): string {
        $platformCategories = self::PLATFORM_CATEGORIES[$platform->toString()] ?? [];
        
        $combinedText = strtolower($rawCategory . ' ' . $title . ' ' . $description);
        
        // Score each category
        $categoryScores = [];
        foreach ($platformCategories as $category => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($combinedText, strtolower($keyword))) {
                    $score += strlen($keyword); // Longer matches get higher scores
                }
            }
            if ($score > 0) {
                $categoryScores[$category] = $score;
            }
        }
        
        // Return highest scoring category or default
        if (!empty($categoryScores)) {
            arsort($categoryScores);
            return ucfirst(array_key_first($categoryScores));
        }
        
        // Fallback to raw category or generic
        return !empty($rawCategory) ? ucfirst(strtolower($rawCategory)) : 'General';
    }

    /**
     * Get mapping statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'service' => 'ProductMapper',
            'supported_platforms' => array_keys(self::PLATFORM_CATEGORIES),
            'supported_currencies' => array_values(self::CURRENCY_MAPPINGS),
            'category_mappings' => array_map(
                fn($categories) => array_keys($categories),
                self::PLATFORM_CATEGORIES
            ),
        ];
    }

    /**
     * Validate scraped data completeness
     * 
     * @param ScrapedProductData $scrapedData
     * @return array Validation results
     */
    /**
     * Validate scraped data completeness
     * 
     * @param ScrapedProductData $scrapedData
     * @return array Validation results
     */
    public function validateScrapedData(ScrapedProductData $scrapedData): array
    {
        $validationErrors = [];

        if (empty(trim($scrapedData->getTitle()))) {
            $validationErrors[] = 'Title is required';
        }

        if ($scrapedData->getPrice() === null || $scrapedData->getPrice()->toFloat() <= 0) {
            $validationErrors[] = 'Valid price is required';
        }

        if (empty(trim($scrapedData->getPriceCurrency()))) {
            $validationErrors[] = 'Currency is required';
        }

        return [
            'valid' => empty($validationErrors),
            'errors' => $validationErrors,
            'completeness_score' => $this->calculateCompletenessScore($scrapedData),
        ];
    }

    /**
     * Calculate data completeness score
     * 
     * @param ScrapedProductData $scrapedData
     * @return float Score between 0 and 1
     */
    private function calculateCompletenessScore(ScrapedProductData $scrapedData): float
    {
        $score = 0;
        $maxScore = 7;

        // Required fields
        if (!empty(trim($scrapedData->getTitle()))) $score++;
        if ($scrapedData->getPrice() && $scrapedData->getPrice()->toFloat() > 0) $score++;
        if (!empty(trim($scrapedData->getPriceCurrency()))) $score++;

        // Optional but valuable fields
        if (!empty(trim($scrapedData->getPlatformCategory()))) $score++;
        if (!empty($scrapedData->getImageUrl())) $score++;
        if ($scrapedData->getRating() !== null) $score++;
        if ($scrapedData->getRatingCount() > 0) $score++;

        return round($score / $maxScore, 2);
    }
}