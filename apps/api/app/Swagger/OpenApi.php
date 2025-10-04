<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="PalmOutsourcing Task (2) - Product Scraping API",
 *     version="1.0.0",
 *     description="RESTful API for scraping and managing e-commerce products from Amazon and Jumia. Provides comprehensive CRUD operations, batch scraping, filtering, pagination, and real-time price monitoring.",
 *     @OA\Contact(
 *         name="API Support",
 *         email="support@example.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\Tag(
 *     name="Products",
 *     description="Product management endpoints - CRUD operations for watched products"
 * )
 *
 * @OA\Tag(
 *     name="Scraping",
 *     description="Product scraping operations - endpoints for scraping products from e-commerce platforms"
 * )
 *
 * @OA\PathItem(path="/api")
 */
class OpenApi
{
    // This class is only a holder for annotations.
}
