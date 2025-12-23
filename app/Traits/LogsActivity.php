<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($this->formatLogMessage($message), $this->enrichContext($context));
    }

    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($this->formatLogMessage($message), $this->enrichContext($context));
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::error($this->formatLogMessage($message), $this->enrichContext($context));
    }

    protected function logDebug(string $message, array $context = []): void
    {
        Log::debug($this->formatLogMessage($message), $this->enrichContext($context));
    }

    protected function formatLogMessage(string $message): string
    {
        return sprintf('[%s] %s', class_basename($this), $message);
    }

    protected function enrichContext(array $context): array
    {
        return array_merge($context, [
            'service' => class_basename($this),
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
