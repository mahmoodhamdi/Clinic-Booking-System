<?php

namespace Tests\Unit\Models;

use App\Enums\Gender;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_has_correct_fillable_attributes(): void
    {
        $user = new User();

        $this->assertEquals([
            'name',
            'email',
            'phone',
            'password',
            'role',
            'date_of_birth',
            'gender',
            'address',
            'avatar',
            'is_active',
            'phone_verified_at',
        ], $user->getFillable());
    }

    /** @test */
    public function user_has_correct_hidden_attributes(): void
    {
        $user = new User();

        $this->assertEquals([
            'password',
            'remember_token',
        ], $user->getHidden());
    }

    /** @test */
    public function user_casts_role_to_enum(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->assertInstanceOf(UserRole::class, $user->role);
        $this->assertEquals(UserRole::ADMIN, $user->role);
    }

    /** @test */
    public function user_casts_gender_to_enum(): void
    {
        $user = User::factory()->create(['gender' => Gender::MALE]);

        $this->assertInstanceOf(Gender::class, $user->gender);
        $this->assertEquals(Gender::MALE, $user->gender);
    }

    /** @test */
    public function user_casts_is_active_to_boolean(): void
    {
        $user = User::factory()->create(['is_active' => 1]);

        $this->assertIsBool($user->is_active);
        $this->assertTrue($user->is_active);
    }

    /** @test */
    public function user_casts_date_of_birth_to_date(): void
    {
        $user = User::factory()->create(['date_of_birth' => '1990-01-15']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->date_of_birth);
        $this->assertEquals('1990-01-15', $user->date_of_birth->format('Y-m-d'));
    }

    /** @test */
    public function is_admin_returns_true_for_admin_role(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->isAdmin());
    }

    /** @test */
    public function is_admin_returns_false_for_non_admin_role(): void
    {
        $user = User::factory()->patient()->create();

        $this->assertFalse($user->isAdmin());
    }

    /** @test */
    public function is_secretary_returns_true_for_secretary_role(): void
    {
        $user = User::factory()->secretary()->create();

        $this->assertTrue($user->isSecretary());
    }

    /** @test */
    public function is_secretary_returns_false_for_non_secretary_role(): void
    {
        $user = User::factory()->patient()->create();

        $this->assertFalse($user->isSecretary());
    }

    /** @test */
    public function is_patient_returns_true_for_patient_role(): void
    {
        $user = User::factory()->patient()->create();

        $this->assertTrue($user->isPatient());
    }

    /** @test */
    public function is_patient_returns_false_for_non_patient_role(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertFalse($user->isPatient());
    }

    /** @test */
    public function is_staff_returns_true_for_admin(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->isStaff());
    }

    /** @test */
    public function is_staff_returns_true_for_secretary(): void
    {
        $user = User::factory()->secretary()->create();

        $this->assertTrue($user->isStaff());
    }

    /** @test */
    public function is_staff_returns_false_for_patient(): void
    {
        $user = User::factory()->patient()->create();

        $this->assertFalse($user->isStaff());
    }

    /** @test */
    public function scope_active_returns_only_active_users(): void
    {
        User::factory()->count(3)->create(['is_active' => true]);
        User::factory()->count(2)->inactive()->create();

        $activeUsers = User::active()->get();

        $this->assertCount(3, $activeUsers);
    }

    /** @test */
    public function scope_patients_returns_only_patients(): void
    {
        User::factory()->admin()->create();
        User::factory()->secretary()->create();
        User::factory()->patient()->count(3)->create();

        $patients = User::patients()->get();

        $this->assertCount(3, $patients);
    }

    /** @test */
    public function scope_staff_returns_admin_and_secretary(): void
    {
        User::factory()->admin()->create();
        User::factory()->secretary()->create();
        User::factory()->patient()->count(3)->create();

        $staff = User::staff()->get();

        $this->assertCount(2, $staff);
    }

    /** @test */
    public function user_can_be_soft_deleted(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertNotNull(User::withTrashed()->find($userId));
    }

    /** @test */
    public function avatar_url_returns_null_when_no_avatar(): void
    {
        $user = User::factory()->create(['avatar' => null]);

        $this->assertNull($user->avatar_url);
    }

    /** @test */
    public function avatar_url_returns_full_url_when_avatar_exists(): void
    {
        $user = User::factory()->create(['avatar' => 'avatars/test.jpg']);

        $this->assertStringContainsString('storage/avatars/test.jpg', $user->avatar_url);
    }

    /** @test */
    public function password_is_hashed_automatically(): void
    {
        $user = User::factory()->create(['password' => 'plainpassword']);

        $this->assertNotEquals('plainpassword', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('plainpassword', $user->password));
    }
}
