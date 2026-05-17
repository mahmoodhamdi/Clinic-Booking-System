<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    /**
     * Return a success response.
     */
    protected function success($data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error response.
     */
    protected function error(string $message, $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a created response.
     */
    protected function created($data = null, ?string $message = null): JsonResponse
    {
        return $this->success($data, $message ?? __('messages.common.created'), 201);
    }

    /**
     * Return a deleted response.
     */
    protected function deleted(?string $message = null): JsonResponse
    {
        return $this->success(null, $message ?? __('messages.common.deleted'));
    }

    /**
     * Return a not found response.
     */
    protected function notFound(?string $message = null): JsonResponse
    {
        return $this->error($message ?? __('messages.common.not_found'), null, 404);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorized(?string $message = null): JsonResponse
    {
        return $this->error($message ?? __('messages.common.unauthorized'), null, 401);
    }

    /**
     * Return a forbidden response.
     */
    protected function forbidden(?string $message = null): JsonResponse
    {
        return $this->error($message ?? __('messages.common.forbidden'), null, 403);
    }

    /**
     * Return a validation error response.
     */
    protected function validationError($errors): JsonResponse
    {
        return $this->error(__('messages.common.invalid_data'), $errors, 422);
    }
}
