<?php

declare(strict_types=1);

namespace Domain\Product\Service;

/**
 * URL Sanitizer Service
 * 
 * Normalizes product URLs to prevent duplicates caused by:
 * - Query parameters (tracking codes, session IDs, etc.)
 * - Trailing slashes
 * - URL fragments (#section)
 * - www prefix variations
 * 
 * Requirements Implemented:
 * - REQ-DATA-001: System SHALL prevent duplicate products with different URL variations
 * - REQ-URL-001: URLs SHALL be normalized before storage and comparison
 */
class UrlSanitizer
{
    /**
     * Sanitize a URL by removing query parameters, trailing slashes, and fragments
     * 
     * Examples:
     * - https://amazon.com/dp/B001?ref=xyz&utm=123 → https://amazon.com/dp/B001
     * - https://jumia.com.eg/product/ → https://jumia.com.eg/product
     * - https://www.amazon.com/dp/B001#reviews → https://amazon.com/dp/B001
     * 
     * @param string $url The URL to sanitize
     * @return string The sanitized URL
     */
    public static function sanitize(string $url): string
    {
        $parsed = parse_url($url);
        
        if ($parsed === false || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            // If URL cannot be parsed, return as-is
            return $url;
        }

        // Build base URL with scheme and host
        $sanitized = $parsed['scheme'] . '://';
        
        // Remove 'www.' prefix for consistency
        $host = $parsed['host'];
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }
        
        $sanitized .= $host;
        
        // Add port if non-standard
        if (isset($parsed['port'])) {
            $standardPorts = ['http' => 80, 'https' => 443];
            if (!isset($standardPorts[$parsed['scheme']]) || $parsed['port'] != $standardPorts[$parsed['scheme']]) {
                $sanitized .= ':' . $parsed['port'];
            }
        }
        
        // Add path (remove trailing slash)
        if (isset($parsed['path'])) {
            $path = rtrim($parsed['path'], '/');
            // Keep empty path as empty (don't add trailing slash)
            if ($path !== '') {
                $sanitized .= $path;
            }
        }
        
        // Explicitly DO NOT add query parameters or fragments
        // This ensures all URLs are compared without query strings
        
        return $sanitized;
    }

    /**
     * Check if two URLs are equivalent after sanitization
     * 
     * @param string $url1 First URL
     * @param string $url2 Second URL
     * @return bool True if URLs are equivalent
     */
    public static function areEquivalent(string $url1, string $url2): bool
    {
        return self::sanitize($url1) === self::sanitize($url2);
    }

    /**
     * Extract product identifier from common e-commerce URL patterns
     * 
     * Amazon: /dp/PRODUCTID or /gp/product/PRODUCTID
     * Jumia: /product-name-PRODUCTID.html
     * 
     * @param string $url Product URL
     * @return string|null Product ID if found
     */
    public static function extractProductId(string $url): ?string
    {
        // Amazon pattern: /dp/PRODUCTID
        if (preg_match('#/dp/([A-Z0-9]+)#i', $url, $matches)) {
            return $matches[1];
        }

        // Amazon pattern: /gp/product/PRODUCTID
        if (preg_match('#/gp/product/([A-Z0-9]+)#i', $url, $matches)) {
            return $matches[1];
        }

        // Jumia pattern: ends with -PRODUCTID.html
        if (preg_match('#-([a-z0-9]+)\.html$#i', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Validate that a URL is in a sanitized format
     * 
     * @param string $url URL to check
     * @return bool True if URL is already sanitized
     */
    public static function isSanitized(string $url): bool
    {
        $parsed = parse_url($url);
        
        if ($parsed === false) {
            return false;
        }

        // Check for query parameters
        if (isset($parsed['query']) && !empty($parsed['query'])) {
            return false;
        }

        // Check for fragments
        if (isset($parsed['fragment'])) {
            return false;
        }

        // Check for trailing slash (except root path)
        if (isset($parsed['path']) && $parsed['path'] !== '/' && str_ends_with($parsed['path'], '/')) {
            return false;
        }

        // Check for www prefix
        if (isset($parsed['host']) && str_starts_with($parsed['host'], 'www.')) {
            return false;
        }

        return true;
    }
}
