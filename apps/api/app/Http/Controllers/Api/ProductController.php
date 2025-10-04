<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiStdResponse;
use App\UseCases\CreateProductUseCase;
use App\UseCases\DeleteProductUseCase;
use App\UseCases\FetchProductsUseCase;
use App\UseCases\ToggleWatchProductUseCase;
use App\UseCases\UpdateProductUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

/**
 * Product API Controller
 * 
 * RESTful API controller for product CRUD operations.
 * Provides endpoints for managing watched products.
 * 
 * Requirements Implemented:
 * - REQ-API-001: System SHALL provide comprehensive CRUD endpoints for product management
 * - REQ-API-002: System SHALL implement GET endpoint /api/products for retrieving products
 * - REQ-API-003: System SHALL implement POST endpoint /api/products for creating new watched products
 * - REQ-API-004: System SHALL implement GET endpoint /api/products/{id} for retrieving a single product
 * - REQ-API-005: System SHALL implement PUT/PATCH endpoint /api/products/{id} for updating products
 * - REQ-API-006: System SHALL implement DELETE endpoint /api/products/{id} for removing watched products
 * - REQ-API-007: All endpoints SHALL implement proper data validation
 * - REQ-API-008: All endpoints SHALL implement comprehensive error handling
 * - REQ-API-009: Error responses SHALL include descriptive error messages and appropriate HTTP status codes
 * - REQ-API-010: Validation errors SHALL return 422 status code with detailed field-level errors
 * - REQ-ARCH-008: Controllers SHALL utilize use-cases and remain thin
 * - REQ-FILTER-001 to REQ-FILTER-013: Filtering and pagination requirements
 * 
 * @OA\Tag(
 *     name="Products",
 *     description="Product management endpoints - CRUD operations for watched products"
 * )
 */
class ProductController extends Controller
{
    public function __construct(
        private FetchProductsUseCase $fetchProductsUseCase,
        private CreateProductUseCase $createProductUseCase,
        private UpdateProductUseCase $updateProductUseCase,
        private DeleteProductUseCase $deleteProductUseCase,
        private ToggleWatchProductUseCase $toggleWatchProductUseCase
    ) {
    }

    /**
     * List products with filtering and pagination
     * 
     * GET /api/products
     * 
     * Query Parameters:
     * - page (int): Page number (default: 1)
     * - per_page (int): Items per page (default: 15)
     * - platform (string): Filter by platform (amazon/jumia)
     * - search (string): Search in product title
     * - min_price (decimal): Minimum price filter
     * - max_price (decimal): Maximum price filter
     * - min_rating (decimal): Minimum rating filter
     * - max_rating (decimal): Maximum rating filter
     * - category (string): Filter by platform_category
     * - currency (string): Filter by price_currency
     * - is_active (boolean): Filter by active status
     * - sort_by (string): Field to sort by (default: created_at)
     * - sort_order (string): Sort direction (asc/desc, default: desc)
     * 
     * @OA\Get(
     *     path="/api/products",
     *     operationId="getProducts",
     *     tags={"Products"},
     *     summary="List all products with filtering and pagination",
     *     description="Retrieve a paginated list of watched products with optional filtering, searching, and sorting. Supports comprehensive filtering by platform, price range, rating, category, and more. Results are cached for performance.",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=15, example=15)
     *     ),
     *     @OA\Parameter(
     *         name="platform",
     *         in="query",
     *         description="Filter by platform",
     *         required=false,
     *         @OA\Schema(type="string", enum={"amazon", "jumia"}, example="amazon")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in product title",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=255, example="wireless headphones")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Minimum price filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float", minimum=0, example=50.00)
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float", minimum=0, example=500.00)
     *     ),
     *     @OA\Parameter(
     *         name="min_rating",
     *         in="query",
     *         description="Minimum rating filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float", minimum=0, maximum=5, example=4.0)
     *     ),
     *     @OA\Parameter(
     *         name="max_rating",
     *         in="query",
     *         description="Maximum rating filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float", minimum=0, maximum=5, example=5.0)
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by platform category",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=255, example="Electronics")
     *     ),
     *     @OA\Parameter(
     *         name="currency",
     *         in="query",
     *         description="Filter by price currency (ISO 4217)",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=3, example="USD")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by active status",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"created_at", "updated_at", "price", "rating", "title"}, default="created_at", example="price")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort direction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc", example="asc")
     *     ),
     *     @OA\Parameter(
     *         name="created_after",
     *         in="query",
     *         description="Filter products created after this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="created_before",
     *         in="query",
     *         description="Filter products created before this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Product")
     *                 ),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(property="last_page", type="integer", example=7),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=15)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="per_page",
     *                     type="array",
     *                     @OA\Items(type="string", example="The per_page must not be greater than 100.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while fetching products")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'platform' => 'sometimes|string|in:amazon,jumia',
                'search' => 'sometimes|string|max:255',
                'min_price' => 'sometimes|numeric|min:0',
                'max_price' => 'sometimes|numeric|min:0',
                'min_rating' => 'sometimes|numeric|min:0|max:5',
                'max_rating' => 'sometimes|numeric|min:0|max:5',
                'category' => 'sometimes|string|max:255',
                'currency' => 'sometimes|string|size:3',
                'is_active' => 'sometimes|boolean',
                'sort_by' => 'sometimes|string|in:created_at,updated_at,price,rating,title',
                'sort_order' => 'sometimes|string|in:asc,desc',
                'created_after' => 'sometimes|date',
                'created_before' => 'sometimes|date',
            ]);

            Log::info('[PRODUCT-API] Fetching products list', [
                'filters' => $validated,
                'ip' => $request->ip(),
            ]);

            // Extract pagination and sorting
            $page = (int) ($validated['page'] ?? 1);
            $perPage = (int) ($validated['perPage'] ?? 15);
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortOrder = $validated['sort_order'] ?? 'desc';

            // Build filters array
            $filters = array_filter([
                'platform' => $validated['platform'] ?? null,
                'search' => $validated['search'] ?? null,
                'min_price' => $validated['min_price'] ?? null,
                'max_price' => $validated['max_price'] ?? null,
                'min_rating' => $validated['min_rating'] ?? null,
                'max_rating' => $validated['max_rating'] ?? null,
                'category' => $validated['category'] ?? null,
                'currency' => $validated['currency'] ?? null,
                'is_active' => $validated['is_active'] ?? null,
                'created_after' => $validated['created_after'] ?? null,
                'created_before' => $validated['created_before'] ?? null,
            ], fn($value) => $value !== null);

            // Execute use case
            $result = $this->fetchProductsUseCase->execute(
                $filters,
                $page,
                $perPage,
                $sortBy,
                $sortOrder
            );

            if ($result['success']) {
                return ApiStdResponse::successResponse(
                    $result,
                    'Products retrieved successfully',
                    200
                );
            }

            return ApiStdResponse::errorResponse(
                $result['error'] ?? 'Failed to fetch products',
                500,
                [$result]
            );

        } catch (ValidationException $e) {
            return ApiStdResponse::errorResponse(
                'Validation failed',
                422,
                $e->errors()
            );

        } catch (\Exception $e) {
            Log::error('[PRODUCT-API] Error fetching products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiStdResponse::errorResponse(
                'An unexpected error occurred while fetching products',
                500
            );
        }
    }

    /**
     * Get a single product by ID
     * 
     * GET /api/products/{id}
     * 
     * @OA\Get(
     *     path="/api/products/{id}",
     *     operationId="getProductById",
     *     tags={"Products"},
     *     summary="Get a single product by ID",
     *     description="Retrieve detailed information about a specific product by its ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while fetching product")
     *         )
     *     )
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            Log::info('[PRODUCT-API] Fetching product by ID', [
                'product_id' => $id,
            ]);

            $result = $this->fetchProductsUseCase->getById($id);

            if ($result['success']) {
                return ApiStdResponse::successResponse(
                    $result['data'],
                    'Product retrieved successfully',
                    200
                );
            }

            return ApiStdResponse::errorResponse(
                $result['error'] ?? 'Product not found',
                404,
                [$result]
            );

        } catch (\Exception $e) {
            Log::error('[PRODUCT-API] Error fetching product', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiStdResponse::errorResponse(
                'An unexpected error occurred while fetching product',
                500
            );
        }
    }

    /**
     * Create a new product (scrape and store)
     * 
     * POST /api/products
     * 
     * Request Body:
     * - url (required|url): Product URL
     * - platform (required|string): Platform (amazon/jumia)
     * 
     * @OA\Post(
     *     path="/api/products",
     *     operationId="createProduct",
     *     tags={"Products"},
     *     summary="Create a new product by scraping from URL",
     *     description="Scrape product details from the provided URL and create a new watched product. The product will be scraped from the specified platform (Amazon or Jumia) and stored in the database.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Product URL and platform",
     *         @OA\JsonContent(
     *             required={"url", "platform"},
     *             @OA\Property(
     *                 property="url",
     *                 type="string",
     *                 format="uri",
     *                 maxLength=500,
     *                 description="Product URL to scrape",
     *                 example="https://www.amazon.com/dp/B0863TXGM3"
     *             ),
     *             @OA\Property(
     *                 property="platform",
     *                 type="string",
     *                 enum={"amazon", "jumia"},
     *                 description="E-commerce platform",
     *                 example="amazon"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created and scraped successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product created and scraped successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Product already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product already exists")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed or scraping failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="url",
     *                     type="array",
     *                     @OA\Items(type="string", example="The url field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while creating product")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'url' => 'required|url|max:500',
                'platform' => 'required|string|in:amazon,jumia',
            ]);

            Log::info('[PRODUCT-API] Creating new product', [
                'url' => $validated['url'],
                'platform' => $validated['platform'],
                'ip' => $request->ip(),
            ]);

            $result = $this->createProductUseCase->execute(
                $validated['url'],
                $validated['platform']
            );

            if ($result['success']) {
                return ApiStdResponse::successResponse(
                    $result['data'],
                    'Product created and scraped successfully',
                    201
                );
            }

            $statusCode = match ($result['error_code'] ?? '') {
                'PRODUCT_EXISTS' => 409,
                'SCRAPING_FAILED' => 422,
                'VALIDATION_FAILED' => 422,
                default => 500,
            };

            return ApiStdResponse::errorResponse(
                $result['error'] ?? 'Failed to create product',
                $statusCode,
                [$result]
            );

        } catch (ValidationException $e) {
            return ApiStdResponse::errorResponse(
                'Validation failed',
                422,
                $e->errors()
            );

        } catch (\Exception $e) {
            Log::error('[PRODUCT-API] Error creating product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiStdResponse::errorResponse(
                'An unexpected error occurred while creating product',
                500
            );
        }
    }

    /**
     * Update a product (re-scrape or update settings)
     * 
     * PUT/PATCH /api/products/{id}
     * 
     * Request Body:
     * - rescrape (optional|boolean): Whether to trigger re-scraping
     * - is_active (optional|boolean): Update active status
     * 
     * @OA\Patch(
     *     path="/api/products/{id}",
     *     operationId="updateProduct",
     *     tags={"Products"},
     *     summary="Update a product (re-scrape or toggle active status)",
     *     description="Update a product by re-scraping fresh data from its source URL or toggling its active status. At least one parameter (rescrape or is_active) must be provided.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Update parameters",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="rescrape",
     *                 type="boolean",
     *                 description="Trigger re-scraping of product data",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="is_active",
     *                 type="boolean",
     *                 description="Set product active status",
     *                 example=false
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product re-scraped and updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No valid update parameters provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No valid update parameters provided")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation or update failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while updating product")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rescrape' => 'sometimes|boolean',
                'is_active' => 'sometimes|boolean',
            ]);

            Log::info('[PRODUCT-API] Updating product', [
                'product_id' => $id,
                'data' => $validated,
                'ip' => $request->ip(),
            ]);

            // Handle rescrape request
            if (isset($validated['rescrape']) && $validated['rescrape']) {
                $result = $this->updateProductUseCase->executeById($id);
                
                if ($result['success']) {
                    return ApiStdResponse::successResponse(
                        $result['product'],
                        'Product re-scraped and updated successfully',
                        200
                    );
                }

                $statusCode = $result['error_code'] === 'PRODUCT_NOT_FOUND' ? 404 : 422;
                
                return ApiStdResponse::errorResponse(
                    $result['error'] ?? 'Failed to update product',
                    $statusCode,
                    [$result]
                );
            }

            // Handle watch status toggle
            if (isset($validated['is_active'])) {
                $result = $this->toggleWatchProductUseCase->execute(
                    $id,
                    $validated['is_active']
                );
                
                if ($result['success']) {
                    $message = $validated['is_active'] 
                        ? 'Product activated successfully' 
                        : 'Product deactivated successfully';
                        
                    return ApiStdResponse::successResponse(
                        $result['data'],
                        $message,
                        200
                    );
                }

                $statusCode = $result['error_code'] === 'PRODUCT_NOT_FOUND' ? 404 : 422;
                
                return ApiStdResponse::errorResponse(
                    $result['error'] ?? 'Failed to update product',
                    $statusCode,
                    [$result]
                );
            }

            return ApiStdResponse::errorResponse(
                'No valid update parameters provided',
                400
            );

        } catch (ValidationException $e) {
            return ApiStdResponse::errorResponse(
                'Validation failed',
                422,
                $e->errors()
            );

        } catch (\Exception $e) {
            Log::error('[PRODUCT-API] Error updating product', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiStdResponse::errorResponse(
                'An unexpected error occurred while updating product',
                500
            );
        }
    }

    /**
     * Delete a product
     * 
     * DELETE /api/products/{id}
     * 
     * @OA\Delete(
     *     path="/api/products/{id}",
     *     operationId="deleteProduct",
     *     tags={"Products"},
     *     summary="Delete a product",
     *     description="Permanently delete a watched product from the database",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product deleted successfully"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Delete failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to delete product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while deleting product")
     *         )
     *     )
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            Log::info('[PRODUCT-API] Deleting product', [
                'product_id' => $id,
            ]);

            $result = $this->deleteProductUseCase->execute($id);

            if ($result['success']) {
                return ApiStdResponse::successResponse(
                    null,
                    'Product deleted successfully',
                    200
                );
            }

            $statusCode = $result['error_code'] === 'PRODUCT_NOT_FOUND' ? 404 : 422;

            return ApiStdResponse::errorResponse(
                $result['error'] ?? 'Failed to delete product',
                $statusCode,
                [$result]
            );

        } catch (\Exception $e) {
            Log::error('[PRODUCT-API] Error deleting product', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiStdResponse::errorResponse(
                'An unexpected error occurred while deleting product',
                500
            );
        }
    }

    /**
     * Get product statistics
     * 
     * GET /api/products/statistics
     * 
     * @OA\Get(
     *     path="/api/products/statistics",
     *     operationId="getProductStatistics",
     *     tags={"Products"},
     *     summary="Get aggregate product statistics",
     *     description="Retrieve aggregate statistics about watched products including total count, active products, platform breakdown, and average metrics",
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistics retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_products", type="integer", example=150),
     *                 @OA\Property(property="active_products", type="integer", example=120),
     *                 @OA\Property(property="inactive_products", type="integer", example=30),
     *                 @OA\Property(
     *                     property="by_platform",
     *                     type="object",
     *                     @OA\Property(property="amazon", type="integer", example=80),
     *                     @OA\Property(property="jumia", type="integer", example=70)
     *                 ),
     *                 @OA\Property(property="average_price", type="number", format="float", example=45.50),
     *                 @OA\Property(property="average_rating", type="number", format="float", example=4.2),
     *                 @OA\Property(property="total_scrapes", type="integer", example=450)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred while fetching statistics")
     *         )
     *     )
     * )
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            Log::info('[PRODUCT-API] Fetching product statistics');

            $result = $this->fetchProductsUseCase->getStatistics();

            if ($result['success']) {
                return ApiStdResponse::successResponse(
                    $result['statistics'],
                    'Statistics retrieved successfully',
                    200
                );
            }

            return ApiStdResponse::errorResponse(
                $result['error'] ?? 'Failed to fetch statistics',
                500,
                [$result]
            );

        } catch (\Exception $e) {
            Log::error('[PRODUCT-API] Error fetching statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiStdResponse::errorResponse(
                'An unexpected error occurred while fetching statistics',
                500
            );
        }
    }
}
