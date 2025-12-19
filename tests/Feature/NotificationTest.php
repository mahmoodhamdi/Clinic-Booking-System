<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\AppointmentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->create();
    }

    public function test_patient_can_list_notifications(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->patient->id]);

        $this->patient->notify(new AppointmentConfirmed($appointment));
        $this->patient->notify(new AppointmentReminder($appointment));

        Sanctum::actingAs($this->patient);

        $response = $this->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'data',
                        'read_at',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_patient_can_list_notifications_with_pagination(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->patient->id]);

        for ($i = 0; $i < 20; $i++) {
            $this->patient->notify(new AppointmentConfirmed($appointment));
        }

        Sanctum::actingAs($this->patient);

        $response = $this->getJson('/api/notifications?per_page=10');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.last_page', 2);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_patient_can_get_unread_count(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->patient->id]);

        $this->patient->notify(new AppointmentConfirmed($appointment));
        $this->patient->notify(new AppointmentReminder($appointment));

        // Mark one as read
        $this->patient->notifications()->first()->markAsRead();

        Sanctum::actingAs($this->patient);

        $response = $this->getJson('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_patient_can_mark_notification_as_read(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->patient->id]);
        $this->patient->notify(new AppointmentConfirmed($appointment));

        $notification = $this->patient->notifications()->first();
        $this->assertNull($notification->read_at);

        Sanctum::actingAs($this->patient);

        $response = $this->postJson("/api/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJsonPath('message', 'تم تحديد الإشعار كمقروء');

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_patient_can_mark_all_notifications_as_read(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->patient->id]);

        $this->patient->notify(new AppointmentConfirmed($appointment));
        $this->patient->notify(new AppointmentReminder($appointment));
        $this->patient->notify(new AppointmentConfirmed($appointment));

        $this->assertEquals(3, $this->patient->unreadNotifications()->count());

        Sanctum::actingAs($this->patient);

        $response = $this->postJson('/api/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('message', 'تم تحديد جميع الإشعارات كمقروءة');

        $this->patient->refresh();
        $this->assertEquals(0, $this->patient->unreadNotifications()->count());
    }

    public function test_patient_can_delete_notification(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->patient->id]);
        $this->patient->notify(new AppointmentConfirmed($appointment));

        $notification = $this->patient->notifications()->first();
        $notificationId = $notification->id;

        Sanctum::actingAs($this->patient);

        $response = $this->deleteJson("/api/notifications/{$notificationId}");

        $response->assertOk()
            ->assertJsonPath('message', 'تم حذف الإشعار بنجاح');

        $this->assertDatabaseMissing('notifications', ['id' => $notificationId]);
    }

    public function test_patient_cannot_access_other_patient_notifications(): void
    {
        $otherPatient = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $otherPatient->id]);
        $otherPatient->notify(new AppointmentConfirmed($appointment));

        $notification = $otherPatient->notifications()->first();

        Sanctum::actingAs($this->patient);

        $response = $this->postJson("/api/notifications/{$notification->id}/read");

        $response->assertNotFound();
    }

    public function test_patient_cannot_delete_other_patient_notifications(): void
    {
        $otherPatient = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $otherPatient->id]);
        $otherPatient->notify(new AppointmentConfirmed($appointment));

        $notification = $otherPatient->notifications()->first();

        Sanctum::actingAs($this->patient);

        $response = $this->deleteJson("/api/notifications/{$notification->id}");

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
        $this->getJson('/api/notifications/unread-count')->assertUnauthorized();
        $this->postJson('/api/notifications/some-id/read')->assertUnauthorized();
        $this->postJson('/api/notifications/read-all')->assertUnauthorized();
    }

    public function test_notifications_are_ordered_by_latest_first(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->patient->id]);

        // Create notifications with different timestamps
        $this->patient->notify(new AppointmentConfirmed($appointment));
        sleep(1);
        $this->patient->notify(new AppointmentReminder($appointment));

        Sanctum::actingAs($this->patient);

        $response = $this->getJson('/api/notifications');

        $response->assertOk();
        $notifications = $response->json('data');

        // Latest notification should be first
        $this->assertEquals('appointment_reminder', $notifications[0]['data']['type']);
        $this->assertEquals('appointment_confirmed', $notifications[1]['data']['type']);
    }

    public function test_notification_contains_correct_data_structure(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->patient->id]);
        $this->patient->notify(new AppointmentConfirmed($appointment));

        Sanctum::actingAs($this->patient);

        $response = $this->getJson('/api/notifications');

        $response->assertOk();
        $notification = $response->json('data.0');

        $this->assertArrayHasKey('id', $notification);
        $this->assertArrayHasKey('type', $notification);
        $this->assertArrayHasKey('data', $notification);
        $this->assertArrayHasKey('read_at', $notification);
        $this->assertArrayHasKey('created_at', $notification);

        $this->assertEquals('appointment_confirmed', $notification['data']['type']);
        $this->assertArrayHasKey('title', $notification['data']);
        $this->assertArrayHasKey('message', $notification['data']);
        $this->assertArrayHasKey('appointment_id', $notification['data']);
    }

    public function test_empty_notifications_returns_empty_array(): void
    {
        Sanctum::actingAs($this->patient);

        $response = $this->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    }

    public function test_unread_count_returns_zero_when_all_read(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->patient->id]);

        $this->patient->notify(new AppointmentConfirmed($appointment));
        $this->patient->notifications()->first()->markAsRead();

        Sanctum::actingAs($this->patient);

        $response = $this->getJson('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_mark_already_read_notification_is_idempotent(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->patient->id]);
        $this->patient->notify(new AppointmentConfirmed($appointment));

        $notification = $this->patient->notifications()->first();
        $notification->markAsRead();
        $originalReadAt = $notification->read_at;

        Sanctum::actingAs($this->patient);

        $response = $this->postJson("/api/notifications/{$notification->id}/read");

        $response->assertOk();

        // read_at should not change
        $notification->refresh();
        $this->assertEquals($originalReadAt->timestamp, $notification->read_at->timestamp);
    }
}
