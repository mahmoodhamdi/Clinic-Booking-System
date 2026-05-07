<?php

namespace Tests\Feature\Logging;

use App\Logging\JsonLogChannelFactory;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Tests\TestCase;

class StructuredLoggingTest extends TestCase
{
    /** @test */
    public function json_channel_factory_produces_a_logger_writing_json_lines(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'clinic-log-');

        $factory = new JsonLogChannelFactory;
        $logger = $factory([
            'name' => 'test',
            'level' => 'debug',
            'stream' => $tmp,
        ]);

        $this->assertInstanceOf(Logger::class, $logger);

        $logger->info('hello {name}', ['name' => 'world', 'foo' => 'bar']);

        $contents = trim((string) file_get_contents($tmp));
        $this->assertNotSame('', $contents);

        $line = json_decode($contents, true);
        $this->assertIsArray($line);
        $this->assertSame('test', $line['channel']);
        $this->assertSame('INFO', $line['level_name']);
        $this->assertSame('hello world', $line['message']);
        $this->assertSame('bar', $line['context']['foo']);

        @unlink($tmp);
    }

    /** @test */
    public function add_request_id_middleware_pushes_request_id_into_log_context(): void
    {
        // Trigger any authed-or-public route through the middleware stack.
        $response = $this->getJson('/api/health', [
            'X-Request-ID' => 'req-test-123',
        ]);

        $response->assertOk();
        $response->assertHeader('X-Request-ID', 'req-test-123');

        $context = Log::sharedContext();
        $this->assertSame('req-test-123', $context['request_id']);
    }
}
