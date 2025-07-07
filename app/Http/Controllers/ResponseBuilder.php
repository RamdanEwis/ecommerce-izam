<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ResponseBuilder
{
    /**
     * Success response with data
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ], $statusCode);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return JsonResponse
     */
    public static function error(string $message = 'Error occurred', int $statusCode = Response::HTTP_BAD_REQUEST, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     *
     * @param mixed $errors
     * @param string $message
     * @return JsonResponse
     */
    public static function validationError($errors, string $message = 'Validation failed'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->toISOString(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Internal server error response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Created response
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public static function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Updated response
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public static function updated($data = null, string $message = 'Resource updated successfully'): JsonResponse
    {
        return self::success($data, $message, Response::HTTP_OK);
    }

    /**
     * Deleted response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return self::success(null, $message, Response::HTTP_OK);
    }

    /**
     * No content response
     *
     * @return JsonResponse
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Paginated response
     *
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @param string|null $resourceClass
     * @return JsonResponse
     */
    public static function paginated(LengthAwarePaginator $paginator, string $message = 'Data retrieved successfully', string $resourceClass = null): JsonResponse
    {
        $data = $paginator->items();

        // Apply resource transformation if specified
        if ($resourceClass && class_exists($resourceClass)) {
            $data = $resourceClass::collection($data);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            'timestamp' => now()->toISOString(),
        ], Response::HTTP_OK);
    }

    /**
     * Resource response
     *
     * @param JsonResource $resource
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function resource(JsonResource $resource, string $message = 'Data retrieved successfully', int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource,
            'timestamp' => now()->toISOString(),
        ], $statusCode);
    }

    /**
     * Resource collection response
     *
     * @param ResourceCollection $collection
     * @param string $message
     * @return JsonResponse
     */
    public static function collection(ResourceCollection $collection, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $collection,
            'timestamp' => now()->toISOString(),
        ], Response::HTTP_OK);
    }

    /**
     * Custom response with flexible structure
     *
     * @param array $data
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function custom(array $data, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $response = array_merge([
            'timestamp' => now()->toISOString(),
        ], $data);

        return response()->json($response, $statusCode);
    }

    /**
     * Exception response
     *
     * @param \Exception $exception
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function exception(\Exception $exception, string $message = 'An error occurred', int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        // Add exception details in development
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Multiple errors response
     *
     * @param array $errors
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function multipleErrors(array $errors, string $message = 'Multiple errors occurred', int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'error_count' => count($errors),
            'timestamp' => now()->toISOString(),
        ], $statusCode);
    }

    /**
     * Rate limit exceeded response
     *
     * @param string $message
     * @param int $retryAfter
     * @return JsonResponse
     */
    public static function rateLimitExceeded(string $message = 'Rate limit exceeded', int $retryAfter = 60): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'retry_after' => $retryAfter,
            'timestamp' => now()->toISOString(),
        ], Response::HTTP_TOO_MANY_REQUESTS)->header('Retry-After', $retryAfter);
    }
}
