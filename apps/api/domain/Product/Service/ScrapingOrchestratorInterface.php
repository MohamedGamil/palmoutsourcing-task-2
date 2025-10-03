<?php

declare(strict_types=1);

namespace Domain\Product\Service;

/**
 * Scraping Orchestrator Interface
 * 
 * Domain contract for scraping orchestration services.
 * Defines the interface for coordinating complete scraping and mapping workflows
 * between scraping services and product mapping services.
 * 
 * Requirements Implemented:
 * - REQ-INT-001: System SHALL integrate scraping and mapping services
 * - REQ-INT-002: System SHALL provide a unified interface for scraping operations
 * - REQ-INT-003: System SHALL support end-to-end product data processing
 * - REQ-ARCH-006: Domain layer SHALL define service contracts
 */
interface ScrapingOrchestratorInterface
{
    /**
     * Scrape and map a single product from URL
     * 
     * REQ-INT-003: System SHALL support end-to-end product data processing
     * 
     * @param string $url Product URL to scrape and map
     * @return array Complete processing result including raw data, mapped data, and validation
     */
    public function scrapeAndMapProduct(string $url): array;

    /**
     * Scrape and map multiple products from URLs
     * 
     * REQ-INT-002: System SHALL provide a unified interface for scraping operations
     * 
     * @param array $urls Array of product URLs to process
     * @return array Batch processing results with summary and individual results
     */
    public function scrapeAndMapMultipleProducts(array $urls): array;

    /**
     * Test platform capability for scraping and mapping
     * 
     * @param string $platformName Platform to test (amazon, jumia, etc.)
     * @return array Platform capability test results
     */
    public function testPlatformCapability(string $platformName): array;

    /**
     * Get comprehensive health status of all integrated services
     * 
     * @return array Health information for orchestrator, scraping, and mapping services
     */
    public function getHealthStatus(): array;

    /**
     * Get comprehensive statistics for all integrated services
     * 
     * @return array Statistics covering orchestrator capabilities and component services
     */
    public function getStatistics(): array;
}