<?php

namespace Tests\Unit\Http\Middleware;

use App\Enums\UserRole;
use App\Http\Middleware\SecretaryMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecretaryMiddlewareTest extends TestCase
{
    private function pass(): \Closure
    {
        return fn () => new Response('ok', 200);
    }

    /** @test */
    public function unauthenticated_request_gets_403_json_when_expects_json(): void
    {
        $request = Request::create('/x');
        $request->headers->set('Accept', 'application/json');

        $response = (new SecretaryMiddleware)->handle($request, $this->pass());

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertFalse(json_decode($response->getContent(), true)['success']);
    }

    /** @test */
    public function patient_user_is_blocked(): void
    {
        $request = Request::create('/x');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => User::factory()->make(['role' => UserRole::PATIENT]));

        $response = (new SecretaryMiddleware)->handle($request, $this->pass());

        $this->assertSame(403, $response->getStatusCode());
    }

    /** @test */
    public function admin_user_passes_through(): void
    {
        $request = Request::create('/x');
        $request->setUserResolver(fn () => User::factory()->make(['role' => UserRole::ADMIN]));

        $response = (new SecretaryMiddleware)->handle($request, $this->pass());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    /** @test */
    public function secretary_user_passes_through(): void
    {
        $request = Request::create('/x');
        $request->setUserResolver(fn () => User::factory()->make(['role' => UserRole::SECRETARY]));

        $response = (new SecretaryMiddleware)->handle($request, $this->pass());

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function unauthenticated_non_json_request_aborts_with_403(): void
    {
        $request = Request::create('/x');
        // No Accept: application/json — falls through to abort()

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        try {
            (new SecretaryMiddleware)->handle($request, $this->pass());
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            throw $e;
        }
    }
}
