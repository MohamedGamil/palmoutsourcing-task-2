<?php

namespace App\Exceptions;

use App\Http\Responses\ApiStdResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class ApiExceptionHandler extends ApiStdResponse
{
    /**
     * Create a standardized JSON error response
     */
    public static function handle(Throwable $e, Request $request): ?JsonResponse
    {
        // Only handle API requests
        if (!$request->is('api/*')) {
            return null;
        }

        $response = [
            'success' => false,
            'message' => 'An error occurred',
            'errors' => [],
            'data' => null,
        ];

        // Handle specific exception types
        if ($e instanceof ValidationException) {
            return self::handleValidationException($e, $response);
        }

        if ($e instanceof ModelNotFoundException) {
            return self::handleModelNotFoundException($e, $response);
        }

        if ($e instanceof NotFoundHttpException) {
            return self::handleNotFoundHttpException($e, $response);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return self::handleMethodNotAllowedException($e, $response);
        }

        if ($e instanceof UnauthorizedHttpException) {
            return self::handleUnauthorizedException($e, $response);
        }

        if ($e instanceof AccessDeniedHttpException) {
            return self::handleAccessDeniedException($e, $response);
        }

        if ($e instanceof InvalidArgumentException) {
            return self::handleInvalidArgumentException($e, $response);
        }

        if ($e instanceof QueryException) {
            return self::handleQueryException($e, $response);
        }

        // Handle general HTTP exceptions
        if ($e instanceof HttpException) {
            return self::handleHttpException($e, $response);
        }

        // Default server error
        return self::handleGenericException($e, $response);
    }

    private static function handleValidationException(ValidationException $e, array $response): JsonResponse
    {
        $response['message'] = 'The given data was invalid.';
        $response['errors'] = $e->errors();
        
        return response()->json($response, 422);
    }

    private static function handleModelNotFoundException(ModelNotFoundException $e, array $response): JsonResponse
    {
        $model = class_basename($e->getModel());
        $response['message'] = "{$model} not found.";
        
        return response()->json($response, 404);
    }

    private static function handleNotFoundHttpException(NotFoundHttpException $e, array $response): JsonResponse
    {
        $response['message'] = 'The requested resource was not found.';
        
        return response()->json($response, 404);
    }

    private static function handleMethodNotAllowedException(MethodNotAllowedHttpException $e, array $response): JsonResponse
    {
        $response['message'] = 'The specified method for the request is invalid.';
        
        return response()->json($response, 405);
    }

    private static function handleUnauthorizedException(UnauthorizedHttpException $e, array $response): JsonResponse
    {
        $response['message'] = 'Unauthenticated.';
        
        return response()->json($response, 401);
    }

    private static function handleAccessDeniedException(AccessDeniedHttpException $e, array $response): JsonResponse
    {
        $response['message'] = 'This action is unauthorized.';
        
        return response()->json($response, 403);
    }

    private static function handleInvalidArgumentException(InvalidArgumentException $e, array $response): JsonResponse
    {
        $response['message'] = $e->getMessage();
        
        return response()->json($response, 400);
    }

    private static function handleQueryException(QueryException $e, array $response): JsonResponse
    {
        if (app()->environment('local', 'testing')) {
            $response['message'] = 'Database error: ' . $e->getMessage();
            $response['errors'] = [
                'database' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ];
        } else {
            $response['message'] = 'A database error occurred.';
        }
        
        return response()->json($response, 500);
    }

    private static function handleHttpException(HttpException $e, array $response): JsonResponse
    {
        $response['message'] = $e->getMessage() ?: 'An HTTP error occurred.';
        
        return response()->json($response, $e->getStatusCode());
    }

    private static function handleGenericException(Throwable $e, array $response): JsonResponse
    {
        $statusCode = 500;
        
        if (app()->environment('local', 'testing')) {
            $response['message'] = $e->getMessage();
            $response['errors'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->map(function ($trace) {
                    return [
                        'file' => $trace['file'] ?? 'unknown',
                        'line' => $trace['line'] ?? 'unknown',
                        'function' => $trace['function'] ?? 'unknown',
                        'class' => $trace['class'] ?? null,
                    ];
                })->take(5)->toArray(), // Limit trace to 5 entries
            ];
        } else {
            $response['message'] = 'An unexpected error occurred.';
        }
        
        return response()->json($response, $statusCode);
    }
}
