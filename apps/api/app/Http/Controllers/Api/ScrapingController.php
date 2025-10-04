<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiStdResponse;
use App\Services\PlatformDetector;
use App\UseCases\BatchCreateProductsUseCase;
use App\UseCases\CreateProductUseCase;
use App\UseCases\ScrapeProductUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

/**
 * Scraping API Controller
 * 
 * RESTful API controller for product scraping operations.
 * Provides endpoints for scraping individual products and batch operations.
 * 
 * Requirements Implemented:
 * - REQ-API-001: System SHALL provide REST API for scraping operations
 * - REQ-API-002: API SHALL accept product URLs and return structured data
 * - REQ-API-003: API SHALL support batch scraping operations
 * - REQ-API-004: API SHALL return proper HTTP status codes and error messages
 * - REQ-API-005: API SHALL validate input parameters
 * - REQ-ARCH-008: Controllers SHALL utilize use-cases and remain thin
 * 
 * @OA\Tag(
 *     name="Scraping",
 *     description="Product scraping operations - endpoints for scraping products from e-commerce platforms"
 * )
 */
class ScrapingController extends Controller
{
    public function __construct(
        private CreateProductUseCase $createProductUseCase,
        private ScrapeProductUseCase $scrapeProductUseCase,
        private BatchCreateProductsUseCase $batchCreateProductsUseCase
    ) {
    }

    /**
     * Scrape and store a single product
     * 
     * POST /api/scraping/scrape
     * 
     * Platform is automatically detected from the URL domain.
     * 
     * @OA\Post(
     *     path="/api/scraping/scrape",
     *     operationId="scrapeProduct",
     *     tags={"Scraping"},
     *     summary="Scrape and store a single product",
     *     description="Scrape product details from a URL and store it in the database. This endpoint creates a new watched product by fetching data from the e-commerce platform (automatically detected from URL domain).",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Product URL to scrape (platform auto-detected)",
     *         @OA\JsonContent(
     *             required={"url"},
     *             @OA\Property(
     *                 property="url",
     *                 type="string",
     *                 format="uri",
     *                 maxLength=500,
     *                 description="Product URL to scrape (platform auto-detected from domain)",
     *                 example="https://www.amazon.com/dp/B0863TXGM3"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product scraped and stored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product scraped and stored successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Product already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Product already exists for this URL and platform")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation or scraping failed or unsupported platform",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="url",
     *                     type="array",
     *                     @OA\Items(type="string", example="The url must be a valid URL.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function scrapeProduct(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'url' => 'required|url|max:500',
            ]);

            // Auto-detect platform from URL
            try {
                $platform = PlatformDetector::detectPlatformString($validated['url']);
            } catch (\Exception $e) {
                return ApiStdResponse::errorResponse(
                    'Cannot detect platform from URL. Supported platforms: Amazon, Jumia',
                    422,
                    ['url' => $e->getMessage()]
                );
            }

            Log::info('[SCRAPING-API] Single product scrape request', [
                'url' => $validated['url'],
                'platform' => $platform,
                'platform_auto_detected' => true,
                'ip' => $request->ip(),
            ]);

            // Use CreateProductUseCase to scrape and store
            $result = $this->createProductUseCase->execute(
                $validated['url'],
                $platform
            );

            if ($result['success']) {
                return ApiStdResponse::successResponse(
                    $result['data'],
                    'Product scraped and stored successfully',
                    201
                );
            }

            return ApiStdResponse::errorResponse(
                $result['error'] ?? 'Failed to scrape product',
                $result['error_code'] === 'PRODUCT_EXISTS' ? 409 : 422,
                [$result]
            );

        } catch (ValidationException $e) {
            return ApiStdResponse::errorResponse(
                'Validation failed',
                422,
                $e->errors()
            );

        } catch (\Exception $e) {
            Log::error('[SCRAPING-API] Unexpected error in single product scrape', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiStdResponse::errorResponse(
                'An unexpected error occurred while scraping product',
                500
            );
        }
    }

    /**
     * Scrape and store multiple products (batch operation)
     * 
     * POST /api/scraping/batch
     * 
     * Platform is automatically detected from each URL's domain.
     * 
     * @OA\Post(
     *     path="/api/scraping/batch",
     *     operationId="batchScrapeProducts",
     *     tags={"Scraping"},
     *     summary="Batch scrape multiple products",
     *     description="Scrape and store multiple products in a single request. Maximum 50 products per batch. Platform is automatically detected from each URL's domain. Returns aggregated results with success/failure counts.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Array of product URLs to scrape (platforms auto-detected)",
     *         @OA\JsonContent(
     *             required={"urls"},
     *             @OA\Property(
     *                 property="urls",
     *                 type="array",
     *                 minItems=1,
     *                 maxItems=50,
     *                 description="Array of product URLs (platforms auto-detected from domains)",
     *                 @OA\Items(
     *                     type="string",
     *                     format="uri",
     *                     maxLength=500,
     *                     example="https://www.amazon.com/dp/B0863TXGM3"
     *                 )
     *             ),
     *             example={
     *                 "urls": {
     *                     "https://www.amazon.com/dp/B001",
     *                     "https://www.jumia.com.eg/product-123"
     *                 }
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Batch scraping completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Batch scraping completed"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", description="Total products attempted", example=10),
     *                 @OA\Property(property="successful", type="integer", description="Successfully scraped products", example=8),
     *                 @OA\Property(property="failed", type="integer", description="Failed scraping attempts", example=2),
     *                 @OA\Property(
     *                     property="results",
     *                     type="array",
     *                     description="Individual results for each product",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="success", type="boolean", example=true),
     *                         @OA\Property(property="url", type="string", example="https://www.amazon.com/dp/B001"),
     *                         @OA\Property(property="platform", type="string", example="amazon"),
     *                         @OA\Property(property="data", ref="#/components/schemas/Product"),
     *                         @OA\Property(property="error", type="string", nullable=true, example=null)
     *                     )
     *                 )
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
     *                     property="urls",
     *                     type="array",
     *                     @OA\Items(type="string", example="The urls must not have more than 50 items.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred during batch scraping")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function scrapeMultipleProducts(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'urls' => 'required|array|min:1|max:50',
                'urls.*' => 'required|url|max:500',
            ]);

            // Auto-detect platform for each URL
            $urlsWithPlatforms = [];
            foreach ($validated['urls'] as $index => $url) {
                try {
                    $platform = PlatformDetector::detectPlatformString($url);
                    $urlsWithPlatforms[] = [
                        'url' => $url,
                        'platform' => $platform,
                    ];
                } catch (\Exception $e) {
                    // Return error with the problematic URL
                    return ApiStdResponse::errorResponse(
                        "Cannot detect platform for URL at index {$index}: {$url}",
                        422,
                        ['error' => $e->getMessage()]
                    );
                }
            }

            Log::info('[SCRAPING-API] Batch product scrape request', [
                'url_count' => count($urlsWithPlatforms),
                'platforms_auto_detected' => true,
                'ip' => $request->ip(),
            ]);

            // Use BatchCreateProductsUseCase
            $result = $this->batchCreateProductsUseCase->execute($urlsWithPlatforms);

            if ($result['success']) {
                return ApiStdResponse::successResponse(
                    $result,
                    'Batch scraping completed',
                    200
                );
            }

            return ApiStdResponse::errorResponse(
                $result['error'] ?? 'Batch scraping failed',
                422,
                [$result]
            );

        } catch (ValidationException $e) {
            return ApiStdResponse::errorResponse(
                'Validation failed',
                422,
                $e->errors()
            );

        } catch (\Exception $e) {
            Log::error('[SCRAPING-API] Unexpected error in batch scrape', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiStdResponse::errorResponse(
                'An unexpected error occurred during batch scraping',
                500
            );
        }
    }

    /**
     * Manually trigger scraping for existing product
     * 
     * POST /api/scraping/trigger/{productId}
     * 
     * @OA\Post(
     *     path="/api/scraping/trigger/{id}",
     *     operationId="triggerProductScrape",
     *     tags={"Scraping"},
     *     summary="Trigger re-scraping of an existing product",
     *     description="Manually trigger scraping for an existing product to update its data. This endpoint re-fetches product information from the original source URL.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID to re-scrape",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product re-scraped successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product re-scraped successfully"),
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
     *         response=422,
     *         description="Scraping failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to scrape product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @param int $productId
     * @return JsonResponse
     */
    public function triggerScrape(Request $request, int $productId): JsonResponse
    {
        try {
            Log::info('[SCRAPING-API] Manual scrape trigger request', [
                'product_id' => $productId,
                'ip' => $request->ip(),
            ]);

            // Use ScrapeProductUseCase for re-scraping
            $result = $this->scrapeProductUseCase->execute($productId);

            if ($result['success']) {
                return ApiStdResponse::successResponse(
                    $result['data'],
                    'Product re-scraped successfully',
                    200
                );
            }

            return ApiStdResponse::errorResponse(
                $result['error'] ?? 'Failed to scrape product',
                $result['error_code'] === 'PRODUCT_NOT_FOUND' ? 404 : 422,
                [$result]
            );

        } catch (\Exception $e) {
            Log::error('[SCRAPING-API] Error in manual scrape trigger', [
                'error' => $e->getMessage(),
                'product_id' => $productId,
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiStdResponse::errorResponse(
                'An unexpected error occurred while scraping product',
                500
            );
        }
    }

    /**
     * Get supported platforms
     * 
     * GET /api/scraping/platforms
     * 
     * @OA\Get(
     *     path="/api/scraping/platforms",
     *     operationId="getSupportedPlatforms",
     *     tags={"Scraping"},
     *     summary="Get list of supported e-commerce platforms",
     *     description="Retrieve a list of all supported e-commerce platforms with their details, domains, and supported features",
     *     @OA\Response(
     *         response=200,
     *         description="Supported platforms retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Supported platforms retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="platforms",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="amazon"),
     *                         @OA\Property(property="display_name", type="string", example="Amazon"),
     *                         @OA\Property(
     *                             property="domains",
     *                             type="array",
     *                             @OA\Items(type="string"),
     *                             example={"amazon.com", "amazon.co.uk", "amazon.de"}
     *                         ),
     *                         @OA\Property(
     *                             property="supported_features",
     *                             type="array",
     *                             @OA\Items(type="string"),
     *                             example={"scraping", "mapping", "proxy_rotation", "auto_update"}
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred")
     *         )
     *     )
     * )
     *
     * @return JsonResponse
     */
    public function supportedPlatforms(): JsonResponse
    {
        try {
            $platforms = [
                [
                    'name' => 'amazon',
                    'display_name' => 'Amazon',
                    'domains' => ['amazon.com', 'amazon.co.uk', 'amazon.de', 'amazon.fr', 'amazon.ca', 'amazon.eg'],
                    'supported_features' => ['scraping', 'mapping', 'proxy_rotation', 'auto_update'],
                ],
                [
                    'name' => 'jumia',
                    'display_name' => 'Jumia',
                    'domains' => ['jumia.com.eg', 'jumia.com', 'jumia.co.ke', 'jumia.com.ng'],
                    'supported_features' => ['scraping', 'mapping', 'proxy_rotation', 'auto_update'],
                ],
            ];

            return ApiStdResponse::successResponse(
                [
                    'platforms' => $platforms,
                    'total_count' => count($platforms),
                ],
                'Supported platforms retrieved successfully',
                200
            );

        } catch (\Exception $e) {
            Log::error('[SCRAPING-API] Error getting supported platforms', [
                'error' => $e->getMessage(),
            ]);

            return ApiStdResponse::errorResponse(
                'Unable to retrieve supported platforms',
                500
            );
        }
    }
}