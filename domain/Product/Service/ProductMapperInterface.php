<?php

declare(strict_types=1);

namespace Domain\Product\Service;

use Domain\Product\Exception\MappingException;
use Domain\Product\ValueObject\Platform;
use Domain\Product\ValueObject\ProductUrl;

/**
 * Product Mapper Interface
 * 
 * Domain contract for product mapping services.
 * Defines the interface for transforming scraped product data into structured format
 * according to business rules and validation requirements.
 * 
 * Requirements Implemented:
 * - REQ-MAP-001: System SHALL implement a dedicated product mapping service
 * - REQ-MAP-002: Service SHALL transform scraped data into structured format
 * - REQ-MAP-003: Service SHALL validate and normalize product data
 * - REQ-MAP-004: Service SHALL apply business rules for data transformation
 * - REQ-MAP-007: Service SHALL support batch mapping of multiple products
 * - REQ-ARCH-006: Domain layer SHALL define service contracts
 */
interface ProductMapperInterface
{
    /**
     * Map scraped product data to structured array
     * 
     * REQ-MAP-002: Service SHALL transform scraped data into structured format
     * REQ-MAP-003: Service SHALL validate and normalize product data
     * REQ-MAP-004: Service SHALL apply business rules for data transformation
     * 
     * @param ScrapedProductData $scrapedData Raw scraped product data
     * @param Platform $platform Platform the product was scraped from
     * @param ProductUrl $originalUrl Original product URL
     * @return array Structured product data with normalized fields
     * @throws MappingException When mapping fails
     */
    public function mapToProduct(
        ScrapedProductData $scrapedData, 
        Platform $platform, 
        ProductUrl $originalUrl
    ): array;

    /**
     * Map multiple scraped products to structured arrays
     * 
     * REQ-MAP-007: Service SHALL support batch mapping of multiple products
     * 
     * @param array $scrapedDataArray Array of scraped product data with metadata
     * @return array Array of mapping results (successful mappings or errors)
     */
    public function mapMultipleProducts(array $scrapedDataArray): array;

    /**
     * Validate scraped data completeness and correctness
     * 
     * REQ-MAP-003: Service SHALL validate and normalize product data
     * 
     * @param ScrapedProductData $scrapedData Data to validate
     * @return array Validation results with validity status, errors, and completeness score
     */
    public function validateScrapedData(ScrapedProductData $scrapedData): array;

    /**
     * Get mapping service statistics and capabilities
     * 
     * @return array Service statistics including supported platforms, currencies, categories
     */
    public function getStatistics(): array;
}