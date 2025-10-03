<?php

declare(strict_types=1);

namespace Domain\Product\Exception;

use Domain\Product\Exception\DomainException;

/**
 * Unsupported Platform Exception
 * 
 * Thrown when attempting to perform operations on an unsupported platform.
 * 
 * Requirements Implemented:
 * - REQ-ERR-007: System SHALL throw specific exceptions for unsupported platforms
 * - REQ-ERR-008: Exceptions SHALL provide clear error messages and context
 */
class UnsupportedPlatformException extends DomainException
{
    /**
     * Create exception for unsupported platform
     * 
     * @param string $platform Platform name that is not supported
     * @return self
     */
    public static function forPlatform(string $platform): self
    {
        return new self(
            "Platform '{$platform}' is not supported. Supported platforms are: amazon, jumia"
        );
    }

    /**
     * Create exception when platform detection fails
     * 
     * @param string $url URL that couldn't be matched to a platform
     * @return self
     */
    public static function unableToDetectPlatform(string $url): self
    {
        return new self(
            "Unable to detect platform from URL: {$url}"
        );
    }

    /**
     * Create exception for platform configuration issues
     * 
     * @param string $platform Platform name with configuration issues
     * @param string $issue Description of the configuration issue
     * @return self
     */
    public static function configurationError(string $platform, string $issue): self
    {
        return new self(
            "Platform '{$platform}' configuration error: {$issue}"
        );
    }

    /**
     * Create exception when a platform driver is not available
     * 
     * @param string $platform Platform name missing driver
     * @return self
     */
    public static function driverNotAvailable(string $platform): self
    {
        return new self(
            "Driver for platform '{$platform}' is not available or not registered"
        );
    }

    /**
     * Create exception when platform requires specific features not available
     * 
     * @param string $platform Platform name
     * @param string $requiredFeature Feature that is required but not available
     * @return self
     */
    public static function featureNotSupported(string $platform, string $requiredFeature): self
    {
        return new self(
            "Platform '{$platform}' requires feature '{$requiredFeature}' which is not supported"
        );
    }

    /**
     * Create exception when platform URL format is invalid
     * 
     * @param string $platform Platform name
     * @param string $url Invalid URL
     * @param string $expectedFormat Expected URL format
     * @return self
     */
    public static function invalidUrlFormat(string $platform, string $url, string $expectedFormat): self
    {
        return new self(
            "Invalid URL format for platform '{$platform}'. URL: {$url}. Expected format: {$expectedFormat}"
        );
    }

    /**
     * Create exception for temporarily unavailable platforms
     * 
     * @param string $platform Platform name that is temporarily unavailable
     * @param string $reason Reason for unavailability
     * @return self
     */
    public static function temporarilyUnavailable(string $platform, string $reason): self
    {
        return new self(
            "Platform '{$platform}' is temporarily unavailable: {$reason}"
        );
    }
}