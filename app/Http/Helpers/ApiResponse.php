<?php

namespace App\Http\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    /**
     * Return a success response.
     */
    public static function success(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a created response (201).
     */
    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return self::success($data, $message ?? __('تم الإنشاء بنجاح.'), 201);
    }

    /**
     * Return an error response.
     */
    public static function error(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a not found response (404).
     */
    public static function notFound(?string $message = null): JsonResponse
    {
        return self::error($message ?? __('لم يتم العثور على المورد.'), 404);
    }

    /**
     * Return an unauthorized response (401).
     */
    public static function unauthorized(?string $message = null): JsonResponse
    {
        return self::error($message ?? __('غير مصرح.'), 401);
    }

    /**
     * Return a forbidden response (403).
     */
    public static function forbidden(?string $message = null): JsonResponse
    {
        return self::error($message ?? __('الوصول مرفوض.'), 403);
    }

    /**
     * Return a validation error response (422).
     */
    public static function validationError(array $errors, ?string $message = null): JsonResponse
    {
        return self::error($message ?? __('فشل التحقق من صحة البيانات.'), 422, $errors);
    }

    /**
     * Return a too many requests response (429).
     */
    public static function tooManyRequests(?string $message = null): JsonResponse
    {
        return self::error($message ?? __('طلبات كثيرة جداً. يرجى المحاولة لاحقاً.'), 429);
    }

    /**
     * Return a server error response (500).
     */
    public static function serverError(?string $message = null): JsonResponse
    {
        return self::error($message ?? __('حدث خطأ غير متوقع.'), 500);
    }

    /**
     * Return a paginated response.
     */
    public static function paginated(LengthAwarePaginator $paginator, ?string $resourceClass = null): JsonResponse
    {
        $data = $resourceClass
            ? $resourceClass::collection($paginator)
            : $paginator->items();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
