<?php

use App\Http\Controllers\Api\ScrapingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Scraping API Routes
Route::prefix('scraping')->group(function () {
    // Product scraping
    Route::post('product', [ScrapingController::class, 'scrapeProduct']);
    Route::post('products', [ScrapingController::class, 'scrapeMultipleProducts']);
    
    // Platform testing and information
    Route::post('test-platform', [ScrapingController::class, 'testPlatform']);
    Route::get('platforms', [ScrapingController::class, 'supportedPlatforms']);
    
    // Service status and statistics
    Route::get('health', [ScrapingController::class, 'healthStatus']);
    Route::get('statistics', [ScrapingController::class, 'statistics']);
});
