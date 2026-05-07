<?php

namespace Tests\Unit\Http\Middleware;

use App\Enums\UserRole;
use App\Http\Middleware\AdminMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    private function pass(): \Closure
    {
        return fn () => new Response('ok', 200);
    }

    /** @test */
    public function unauthenticated_request_gets_403_json_when_expects_json(): void
    {
        $request = Request::create('/admin/x');
        $request->headers->set('Accept', 'application/json');

        $response = (new AdminMiddleware)->handle($request, $this->pass());

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse(json_decode($response->getContent(), true)['success']);
    }

    /** @test */
    public function unauthenticated_non_json_request_aborts_with_403(): void
    {
        $request = Request::create('/admin/x');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        try {
            (new AdminMiddleware)->handle($request, $this->pass());
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            throw $e;
        }
    }

    /** @test */
    public function patient_user_is_blocked(): void
    {
        $request = Request::create('/admin/x');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => User::factory()->make(['role' => UserRole::PATIENT]));

        $response = (new AdminMiddleware)->handle($request, $this->pass());

        $this->assertSame(403, $response->getStatusCode());
    }

    /** @test */
    public function admin_passes_through(): void
    {
        $request = Request::create('/admin/x');
        $request->setUserResolver(fn () => User::factory()->make(['role' => UserRole::ADMIN]));

        $response = (new AdminMiddleware)->handle($request, $this->pass());

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function secretary_passes_through(): void
    {
        // Same allow-list as admin (the alias allows both staff roles).
        $request = Request::create('/admin/x');
        $request->setUserResolver(fn () => User::factory()->make(['role' => UserRole::SECRETARY]));

        $response = (new AdminMiddleware)->handle($request, $this->pass());

        $this->assertSame(200, $response->getStatusCode());
    }
}
