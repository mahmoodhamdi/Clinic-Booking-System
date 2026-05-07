<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\AuthenticateFromCookie;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class AuthenticateFromCookieTest extends TestCase
{
    private function pass(): \Closure
    {
        return fn () => new Response('ok', 200);
    }

    private function withCookie(string $value): Request
    {
        $request = Request::create('/api/x');
        $request->cookies->set('auth_token', $value);

        return $request;
    }

    /** @test */
    public function existing_authorization_header_is_left_alone(): void
    {
        $request = $this->withCookie('cookie-token');
        $request->headers->set('Authorization', 'Bearer existing-header-token');

        (new AuthenticateFromCookie)->handle($request, $this->pass());

        $this->assertSame('Bearer existing-header-token', $request->headers->get('Authorization'));
    }

    /** @test */
    public function valid_cookie_is_promoted_to_bearer_token(): void
    {
        $request = $this->withCookie('1|abcDEF123');

        (new AuthenticateFromCookie)->handle($request, $this->pass());

        $this->assertSame('Bearer 1|abcDEF123', $request->headers->get('Authorization'));
    }

    /** @test */
    public function cookie_with_invalid_characters_is_ignored(): void
    {
        $request = $this->withCookie('not allowed; chars');

        (new AuthenticateFromCookie)->handle($request, $this->pass());

        $this->assertNull($request->headers->get('Authorization'));
    }

    /** @test */
    public function oversized_cookie_is_ignored(): void
    {
        $request = $this->withCookie(str_repeat('a', 513));

        (new AuthenticateFromCookie)->handle($request, $this->pass());

        $this->assertNull($request->headers->get('Authorization'));
    }

    /** @test */
    public function missing_cookie_is_a_no_op(): void
    {
        $request = Request::create('/api/x'); // no cookie set

        (new AuthenticateFromCookie)->handle($request, $this->pass());

        $this->assertNull($request->headers->get('Authorization'));
    }

    /** @test */
    public function downstream_response_is_returned_unchanged(): void
    {
        $request = $this->withCookie('1|tok');

        $response = (new AuthenticateFromCookie)->handle($request, $this->pass());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }
}
