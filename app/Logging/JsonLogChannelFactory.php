<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

// Custom Monolog factory: writes one JSON object per line to either a file
// or stderr. Used by config/logging.php's "json" channel. Pairs with
// Log::withContext({'request_id': ...}) calls in middleware so every line
// carries the correlation id.
class JsonLogChannelFactory
{
    public function __invoke(array $config): Logger
    {
        $level = $config['level'] ?? 'debug';
        $stream = $config['stream'] ?? storage_path('logs/laravel.log');

        $handler = new StreamHandler($stream, $level);
        $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES, true));

        $logger = new Logger($config['name'] ?? 'json');
        $logger->pushHandler($handler);
        $logger->pushProcessor(new PsrLogMessageProcessor);

        return $logger;
    }
}
