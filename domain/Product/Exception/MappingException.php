<?php

declare(strict_types=1);

namespace Domain\Product\Exception;

/**
 * Mapping Exception
 * 
 * Thrown when product data mapping operations fail.
 * 
 * Requirements Implemented:
 * - REQ-ERR-009: System SHALL throw specific exceptions for mapping failures
 * - REQ-ERR-010: Mapping exceptions SHALL provide detailed error context
 */
class MappingException extends DomainException
{
    /**
     * Create exception for general mapping failure
     * 
     * @param string $productTitle Title of product that failed to map
     * @param string $reason Reason for mapping failure
     * @return self
     */
    public static function failed(string $productTitle, string $reason): self
    {
        return new self(
            "Failed to map product '{$productTitle}': {$reason}"
        );
    }

    /**
     * Create exception for empty required field
     * 
     * @param string $fieldName Name of the empty field
     * @return self
     */
    public static function emptyField(string $fieldName): self
    {
        return new self(
            "Required field '{$fieldName}' is empty or missing"
        );
    }

    /**
     * Create exception for invalid field value
     * 
     * @param string $fieldName Name of the invalid field
     * @param string $reason Reason why the field is invalid
     * @return self
     */
    public static function invalidField(string $fieldName, string $reason): self
    {
        return new self(
            "Invalid value for field '{$fieldName}': {$reason}"
        );
    }

    /**
     * Create exception for data type mismatch
     * 
     * @param string $fieldName Name of the field with type mismatch
     * @param string $expectedType Expected data type
     * @param string $actualType Actual data type received
     * @return self
     */
    public static function typeMismatch(string $fieldName, string $expectedType, string $actualType): self
    {
        return new self(
            "Type mismatch for field '{$fieldName}'. Expected: {$expectedType}, Got: {$actualType}"
        );
    }

    /**
     * Create exception for data validation failure
     * 
     * @param string $fieldName Name of the field that failed validation
     * @param array $validationErrors Array of validation error messages
     * @return self
     */
    public static function validationFailed(string $fieldName, array $validationErrors): self
    {
        $errorsText = implode(', ', $validationErrors);
        return new self(
            "Validation failed for field '{$fieldName}': {$errorsText}"
        );
    }

    /**
     * Create exception for currency conversion failure
     * 
     * @param string $fromCurrency Original currency
     * @param string $toCurrency Target currency
     * @param string $reason Reason for conversion failure
     * @return self
     */
    public static function currencyConversionFailed(string $fromCurrency, string $toCurrency, string $reason): self
    {
        return new self(
            "Failed to convert currency from '{$fromCurrency}' to '{$toCurrency}': {$reason}"
        );
    }

    /**
     * Create exception for unsupported data format
     * 
     * @param string $format The unsupported format
     * @param string $fieldName Field that contains the unsupported format
     * @return self
     */
    public static function unsupportedFormat(string $format, string $fieldName): self
    {
        return new self(
            "Unsupported format '{$format}' for field '{$fieldName}'"
        );
    }

    /**
     * Create exception for missing required dependencies
     * 
     * @param string $dependency Name of missing dependency
     * @param string $operation Operation that requires the dependency
     * @return self
     */
    public static function missingDependency(string $dependency, string $operation): self
    {
        return new self(
            "Missing dependency '{$dependency}' required for operation: {$operation}"
        );
    }

    /**
     * Create exception for data transformation failure
     * 
     * @param string $operation Name of transformation operation
     * @param string $data Data that failed to transform
     * @param string $reason Reason for transformation failure
     * @return self
     */
    public static function transformationFailed(string $operation, string $data, string $reason): self
    {
        return new self(
            "Data transformation '{$operation}' failed for data '{$data}': {$reason}"
        );
    }

    /**
     * Create exception for incomplete data
     * 
     * @param array $missingFields Array of missing required fields
     * @return self
     */
    public static function incompleteData(array $missingFields): self
    {
        $fieldsText = implode(', ', $missingFields);
        return new self(
            "Incomplete data. Missing required fields: {$fieldsText}"
        );
    }
}