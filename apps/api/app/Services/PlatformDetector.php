<?php

declare(strict_types=1);

namespace App\Services;

use Domain\Product\ValueObject\Platform;
use Domain\Product\Exception\InvalidPlatformException;

/**
 * Platform Detector Service
 * 
 * Automatically detects the e-commerce platform from a product URL.
 * Uses domain pattern matching to identify Amazon or Jumia platforms.
 * 
 * Requirements Implemented:
 * - REQ-API-011: System SHALL automatically detect platform from URL domain
 * - REQ-VAL-007: URL domain must match supported platform patterns
 */
class PlatformDetector
{
    /**
     * Detect platform from URL
     * 
     * @param string $url Product URL
     * @return Platform Detected platform
     * @throws InvalidPlatformException If platform cannot be detected
     */
    public static function detectFromUrl(string $url): Platform
    {
        // Try Amazon
        try {
            $amazon = Platform::amazon();
            if ($amazon->matchesUrl($url)) {
                return $amazon;
            }
        } catch (\Exception $e) {
            // Continue to next platform
        }

        // Try Jumia
        try {
            $jumia = Platform::jumia();
            if ($jumia->matchesUrl($url)) {
                return $jumia;
            }
        } catch (\Exception $e) {
            // Continue
        }

        // If no platform matched, throw exception
        throw InvalidPlatformException::cannotDetect($url);
    }

    /**
     * Check if URL belongs to a supported platform
     * 
     * @param string $url Product URL
     * @return bool True if platform can be detected
     */
    public static function canDetect(string $url): bool
    {
        try {
            self::detectFromUrl($url);
            return true;
        } catch (InvalidPlatformException $e) {
            return false;
        }
    }

    /**
     * Get the platform name as a string from URL
     * 
     * @param string $url Product URL
     * @return string Platform name (amazon/jumia)
     * @throws InvalidPlatformException If platform cannot be detected
     */
    public static function detectPlatformString(string $url): string
    {
        $platform = self::detectFromUrl($url);
        return $platform->toString();
    }
}
