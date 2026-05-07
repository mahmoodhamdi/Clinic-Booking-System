<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The Web AuthController exists for the legacy Blade-rendered login flow
// (the SPA frontend uses /api/auth/* directly). These tests exercise the
// view-rendering GETs and the no-op redirects to keep the file from
// staying at 0% coverage. Full POST form submission is covered by the
// equivalent API-level tests in tests/Feature/Api/Auth.
class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function show_login_form_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    /** @test */
    public function show_registration_form_renders(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertViewIs('auth.register');
    }

    /** @test */
    public function show_forgot_password_form_renders(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertViewIs('auth.forgot-password');
    }

    /** @test */
    public function authenticated_user_visiting_login_is_redirected(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect();
    }

    /** @test */
    public function logout_requires_auth(): void
    {
        $this->post('/logout')->assertRedirect('/login');
    }
}
