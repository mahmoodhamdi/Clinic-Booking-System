<?php

namespace Tests\Feature\Api\Admin;

use App\Models\User;
use App\Models\Vacation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VacationApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_list_vacations(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        Vacation::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/vacations');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function admin_can_filter_upcoming_vacations(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        Vacation::factory()->past()->create();
        Vacation::factory()->future()->create();

        $response = $this->getJson('/api/admin/vacations?status=upcoming');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function admin_can_create_vacation(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/vacations', [
            'title' => 'عطلة عيد الفطر',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'reason' => 'إجازة رسمية',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'عطلة عيد الفطر',
                    'days_count' => 6,
                ],
            ]);

        $this->assertDatabaseHas('vacations', [
            'title' => 'عطلة عيد الفطر',
        ]);
    }

    /** @test */
    public function admin_can_get_single_vacation(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $vacation = Vacation::factory()->create(['title' => 'إجازة اختبار']);

        $response = $this->getJson("/api/admin/vacations/{$vacation->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'إجازة اختبار',
                ],
            ]);
    }

    /** @test */
    public function admin_can_update_vacation(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $vacation = Vacation::factory()->future()->create();

        $response = $this->putJson("/api/admin/vacations/{$vacation->id}", [
            'title' => 'عنوان محدث',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'عنوان محدث',
                ],
            ]);
    }

    /** @test */
    public function admin_can_delete_vacation(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $vacation = Vacation::factory()->create();

        $response = $this->deleteJson("/api/admin/vacations/{$vacation->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('vacations', ['id' => $vacation->id]);
    }

    /** @test */
    public function cannot_create_vacation_in_past(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/vacations', [
            'title' => 'إجازة سابقة',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    /** @test */
    public function end_date_must_be_after_start_date(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/vacations', [
            'title' => 'إجازة',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function vacation_title_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/vacations', [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function non_admin_cannot_access_vacations(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $response = $this->getJson('/api/admin/vacations');

        $response->assertForbidden();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_vacations(): void
    {
        $response = $this->getJson('/api/admin/vacations');

        $response->assertUnauthorized();
    }
}
