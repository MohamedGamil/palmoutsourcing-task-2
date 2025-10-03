<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Standardized API Response Format
 *
 * This class provides static methods to generate standardized JSON responses
 * for both success and error scenarios in the API.
 */
class ApiStdResponse
{
    /**
     * Get standard error response format
     */
    public static function errorResponse(string $message, int $statusCode = 500, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'data' => null,
        ], $statusCode);
    }

    /**
     * Get standard success response format
     */
    public static function successResponse($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => [],
        ], $statusCode);
    }
}
