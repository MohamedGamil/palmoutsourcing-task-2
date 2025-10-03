<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ScrapingOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
 */
class ScrapingController extends Controller
{
    private ScrapingOrchestrator $scrapingOrchestrator;

    public function __construct(ScrapingOrchestrator $scrapingOrchestrator)
    {
        $this->scrapingOrchestrator = $scrapingOrchestrator;
    }

    /**
     * Scrape a single product
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

            Log::info('[SCRAPING-API] Single product scrape request', [
                'url' => $validated['url'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $result = $this->scrapingOrchestrator->scrapeAndMapProduct($validated['url']);

            if ($result['status'] === 'success') {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                    'message' => 'Product scraped and mapped successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'url' => $validated['url'],
                    'message' => 'Failed to scrape product',
                ], 422);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
                'message' => 'Invalid input parameters',
            ], 400);

        } catch (\Exception $e) {
            Log::error('[SCRAPING-API] Unexpected error in single product scrape', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Scrape multiple products
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function scrapeMultipleProducts(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'urls' => 'required|array|min:1|max:10',
                'urls.*' => 'required|url|max:500',
            ]);

            Log::info('[SCRAPING-API] Batch product scrape request', [
                'url_count' => count($validated['urls']),
                'urls' => $validated['urls'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $result = $this->scrapingOrchestrator->scrapeAndMapMultipleProducts($validated['urls']);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Batch scraping completed',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
                'message' => 'Invalid input parameters',
            ], 400);

        } catch (\Exception $e) {
            Log::error('[SCRAPING-API] Unexpected error in batch scrape', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Test platform capability
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function testPlatform(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'platform' => 'required|string|in:amazon,jumia',
            ]);

            Log::info('[SCRAPING-API] Platform capability test request', [
                'platform' => $validated['platform'],
                'ip' => $request->ip(),
            ]);

            $result = $this->scrapingOrchestrator->testPlatformCapability($validated['platform']);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Platform capability test completed',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
                'message' => 'Invalid input parameters',
            ], 400);

        } catch (\Exception $e) {
            Log::error('[SCRAPING-API] Error in platform test', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Get service health status
     * 
     * @return JsonResponse
     */
    public function healthStatus(): JsonResponse
    {
        try {
            Log::debug('[SCRAPING-API] Health status request');

            $healthData = $this->scrapingOrchestrator->getHealthStatus();

            return response()->json([
                'success' => true,
                'data' => $healthData,
                'message' => 'Service health status retrieved',
            ], 200);

        } catch (\Exception $e) {
            Log::error('[SCRAPING-API] Error getting health status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'Unable to retrieve health status',
            ], 500);
        }
    }

    /**
     * Get service statistics
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            Log::debug('[SCRAPING-API] Statistics request');

            $statsData = $this->scrapingOrchestrator->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $statsData,
                'message' => 'Service statistics retrieved',
            ], 200);

        } catch (\Exception $e) {
            Log::error('[SCRAPING-API] Error getting statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'Unable to retrieve statistics',
            ], 500);
        }
    }

    /**
     * Get supported platforms
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
                    'supported_features' => ['scraping', 'mapping', 'proxy_rotation'],
                ],
                [
                    'name' => 'jumia',
                    'display_name' => 'Jumia',
                    'domains' => ['jumia.com.eg', 'jumia.com', 'jumia.co.ke', 'jumia.com.ng'],
                    'supported_features' => ['scraping', 'mapping', 'proxy_rotation'],
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'platforms' => $platforms,
                    'total_count' => count($platforms),
                ],
                'message' => 'Supported platforms retrieved',
            ], 200);

        } catch (\Exception $e) {
            Log::error('[SCRAPING-API] Error getting supported platforms', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'Unable to retrieve supported platforms',
            ], 500);
        }
    }
}