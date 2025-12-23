<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BusinessLogicException extends Exception
{
    protected string $errorCode;
    protected array $context;

    public function __construct(
        string $message,
        string $errorCode = 'BUSINESS_ERROR',
        array $context = [],
        int $httpCode = 422
    ) {
        parent::__construct($message, $httpCode);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'context' => $this->context,
        ], $this->getCode());
    }
}
