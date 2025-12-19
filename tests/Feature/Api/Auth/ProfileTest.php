<?php

namespace Tests\Feature\Api\Auth;

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_get_own_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'email', 'phone', 'role']
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
            ]);
    }

    /** @test */
    public function user_can_update_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'Updated Name',
            'address' => 'New Address',
            'gender' => 'male',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'address' => 'New Address',
            'gender' => 'male',
        ]);
    }

    /** @test */
    public function user_can_update_email(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'email' => 'newemail@example.com',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'newemail@example.com',
        ]);
    }

    /** @test */
    public function email_must_be_unique_when_updating(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_can_keep_own_email_when_updating(): void
    {
        $user = User::factory()->create(['email' => 'myemail@example.com']);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'email' => 'myemail@example.com',
            'name' => 'Updated Name',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/change-password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        // Verify new password works
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $user->fresh()->password));
    }

    /** @test */
    public function password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /** @test */
    public function user_can_upload_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/auth/avatar', [
            'avatar' => $file,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    /** @test */
    public function avatar_must_be_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/auth/avatar', [
            'avatar' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    /** @test */
    public function user_can_delete_account(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $userId = $user->id;

        $response = $this->deleteJson('/api/auth/account');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('users', ['id' => $userId]);
    }

    /** @test */
    public function delete_account_revokes_all_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('token1');
        $user->createToken('token2');
        Sanctum::actingAs($user);

        $this->deleteJson('/api/auth/account');

        $this->assertEquals(0, $user->tokens()->count());
    }

    /** @test */
    public function unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertUnauthorized();
    }

    /** @test */
    public function user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['token'],
            ]);

        $this->assertNotNull($response->json('data.token'));
    }
}
