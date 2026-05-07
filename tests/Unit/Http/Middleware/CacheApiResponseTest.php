<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\CacheApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class CacheApiResponseTest extends TestCase
{
    private function passWith(string $body, int $status = 200): \Closure
    {
        return function () use ($body, $status) {
            $r = new Response($body, $status);
            $r->headers->set('Content-Type', 'application/json');

            return $r;
        };
    }

    /** @test */
    public function non_get_methods_pass_through_without_etag(): void
    {
        // Symfony Response auto-adds 'no-cache, private' to any response that
        // hasn't explicitly set Cache-Control, so we can't assert null on
        // that header — only that ETag (the middleware's own addition) is absent.
        $request = Request::create('/x', 'POST');

        $response = (new CacheApiResponse)->handle($request, $this->passWith('{"a":1}'));

        $this->assertNull($response->headers->get('ETag'));
    }

    /** @test */
    public function non_2xx_responses_pass_through_without_etag(): void
    {
        $request = Request::create('/x');

        $response = (new CacheApiResponse)->handle($request, $this->passWith('{"err":1}', 500));

        $this->assertNull($response->headers->get('ETag'));
    }

    /** @test */
    public function authenticated_get_marks_response_private_no_cache(): void
    {
        $request = Request::create('/x');
        $request->setUserResolver(fn () => User::factory()->make());

        $response = (new CacheApiResponse)->handle($request, $this->passWith('{"a":1}'));

        // Symfony reorders Cache-Control directives alphabetically — assert
        // both directives are present rather than a specific ordering.
        $cc = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cc);
        $this->assertStringContainsString('no-cache', $cc);
        $this->assertNull($response->headers->get('ETag')); // no etag for personalised content
    }

    /** @test */
    public function unauthenticated_get_sets_cache_control_and_etag(): void
    {
        $request = Request::create('/public');

        $response = (new CacheApiResponse)->handle($request, $this->passWith('{"a":1}'));

        $cc = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cc);
        $this->assertStringContainsString('max-age=60', $cc);
        $this->assertNotNull($response->headers->get('ETag'));
    }

    /** @test */
    public function custom_max_age_is_honored(): void
    {
        $request = Request::create('/public');

        $response = (new CacheApiResponse)->handle($request, $this->passWith('{"a":1}'), 300);

        $cc = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cc);
        $this->assertStringContainsString('max-age=300', $cc);
    }

    /** @test */
    public function matching_if_none_match_returns_304_with_empty_body(): void
    {
        $body = '{"hello":"world"}';
        $etag = '"'.md5($body).'"';

        $request = Request::create('/public');
        $request->headers->set('If-None-Match', $etag);

        $response = (new CacheApiResponse)->handle($request, $this->passWith($body));

        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    /** @test */
    public function non_matching_if_none_match_returns_full_response(): void
    {
        $body = '{"hello":"world"}';

        $request = Request::create('/public');
        $request->headers->set('If-None-Match', '"stale-etag"');

        $response = (new CacheApiResponse)->handle($request, $this->passWith($body));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($body, $response->getContent());
    }
}
