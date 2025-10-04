<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ScrapingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * Product API Routes
 * 
 * RESTful endpoints for product CRUD operations
 * Implements REQ-API-001 through REQ-API-006
 */
Route::prefix('products')->group(function () {
    // GET /api/products - List products with filtering/pagination (REQ-API-002)
    Route::get('/', [ProductController::class, 'index']);
    
    // GET /api/products/statistics - Get product statistics
    Route::get('statistics', [ProductController::class, 'statistics']);
    
    // GET /api/products/{id} - Get single product (REQ-API-004)
    Route::get('{id}', [ProductController::class, 'show'])->where('id', '[0-9]+');
    
    // POST /api/products - Create new product (scrape and store) (REQ-API-003)
    Route::post('/', [ProductController::class, 'store']);
    
    // PUT/PATCH /api/products/{id} - Update product (re-scrape or settings) (REQ-API-005)
    Route::match(['put', 'patch'], '{id}', [ProductController::class, 'update'])->where('id', '[0-9]+');
    
    // DELETE /api/products/{id} - Delete product (REQ-API-006)
    Route::delete('{id}', [ProductController::class, 'destroy'])->where('id', '[0-9]+');
});

/**
 * Scraping API Routes
 * 
 * Specialized endpoints for scraping operations
 * Implements REQ-SCRAPE requirements
 */
Route::prefix('scraping')->group(function () {
    // POST /api/scraping/scrape - Scrape and create new product (REQ-SCRAPE-010)
    Route::post('scrape', [ScrapingController::class, 'scrapeProduct']);
    
    // POST /api/scraping/batch - Batch scrape multiple products (REQ-SCRAPE-011)
    Route::post('batch', [ScrapingController::class, 'scrapeMultipleProducts']);
    
    // POST /api/scraping/trigger/{id} - Trigger re-scraping of existing product
    Route::post('trigger/{id}', [ScrapingController::class, 'triggerScrape'])->where('id', '[0-9]+');
    
    // GET /api/scraping/platforms - Get supported platforms (REQ-SCRAPE-002)
    Route::get('platforms', [ScrapingController::class, 'supportedPlatforms']);
});
