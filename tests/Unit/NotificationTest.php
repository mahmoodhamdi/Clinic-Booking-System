<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\User;
use App\Notifications\AppointmentCancelled;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\AppointmentReminder;
use App\Notifications\PaymentReceived;
use App\Notifications\PrescriptionReady;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = app(NotificationService::class);
    }

    public function test_appointment_confirmed_notification_structure(): void
    {
        $appointment = Appointment::factory()->create();
        $notification = new AppointmentConfirmed($appointment);

        $data = $notification->toArray($appointment->patient);

        $this->assertEquals('appointment_confirmed', $data['type']);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('appointment_id', $data);
        $this->assertEquals($appointment->id, $data['appointment_id']);
    }

    public function test_appointment_reminder_notification_structure(): void
    {
        $appointment = Appointment::factory()->create();
        $notification = new AppointmentReminder($appointment);

        $data = $notification->toArray($appointment->patient);

        $this->assertEquals('appointment_reminder', $data['type']);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('appointment_id', $data);
        $this->assertArrayHasKey('appointment_date', $data);
        $this->assertArrayHasKey('appointment_time', $data);
    }

    public function test_appointment_cancelled_notification_structure(): void
    {
        $appointment = Appointment::factory()->create();
        $notification = new AppointmentCancelled($appointment, 'admin', 'Schedule conflict');

        $data = $notification->toArray($appointment->patient);

        $this->assertEquals('appointment_cancelled', $data['type']);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('appointment_id', $data);
        $this->assertArrayHasKey('cancelled_by', $data);
        $this->assertEquals('admin', $data['cancelled_by']);
        $this->assertEquals('Schedule conflict', $data['reason']);
    }

    public function test_prescription_ready_notification_structure(): void
    {
        $patient = User::factory()->create();
        $medicalRecord = MedicalRecord::factory()->create(['patient_id' => $patient->id]);
        $prescription = Prescription::factory()->create(['medical_record_id' => $medicalRecord->id]);

        $notification = new PrescriptionReady($prescription);

        $data = $notification->toArray($patient);

        $this->assertEquals('prescription_ready', $data['type']);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('prescription_id', $data);
        $this->assertArrayHasKey('prescription_number', $data);
    }

    public function test_payment_received_notification_structure(): void
    {
        $payment = Payment::factory()->create();
        $notification = new PaymentReceived($payment);

        $data = $notification->toArray($payment->appointment->patient);

        $this->assertEquals('payment_received', $data['type']);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('payment_id', $data);
        $this->assertArrayHasKey('amount', $data);
        $this->assertArrayHasKey('formatted_amount', $data);
    }

    public function test_notification_service_sends_appointment_confirmed(): void
    {
        Notification::fake();

        $appointment = Appointment::factory()->create();

        $this->notificationService->sendAppointmentConfirmed($appointment);

        Notification::assertSentTo(
            $appointment->patient,
            AppointmentConfirmed::class
        );
    }

    public function test_notification_service_sends_appointment_reminder(): void
    {
        Notification::fake();

        $appointment = Appointment::factory()->create();

        $this->notificationService->sendAppointmentReminder($appointment);

        Notification::assertSentTo(
            $appointment->patient,
            AppointmentReminder::class
        );
    }

    public function test_notification_service_sends_appointment_cancelled(): void
    {
        Notification::fake();

        $appointment = Appointment::factory()->create();

        $this->notificationService->sendAppointmentCancelled($appointment, 'admin', 'Test reason');

        Notification::assertSentTo(
            $appointment->patient,
            AppointmentCancelled::class
        );
    }

    public function test_notification_service_sends_prescription_ready(): void
    {
        Notification::fake();

        $patient = User::factory()->create();
        $medicalRecord = MedicalRecord::factory()->create(['patient_id' => $patient->id]);
        $prescription = Prescription::factory()->create(['medical_record_id' => $medicalRecord->id]);

        $this->notificationService->sendPrescriptionReady($prescription);

        Notification::assertSentTo(
            $patient,
            PrescriptionReady::class
        );
    }

    public function test_notification_service_sends_payment_received(): void
    {
        Notification::fake();

        $payment = Payment::factory()->create();

        $this->notificationService->sendPaymentReceived($payment);

        Notification::assertSentTo(
            $payment->appointment->patient,
            PaymentReceived::class
        );
    }

    public function test_notification_service_gets_unread_count(): void
    {
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        // Send some notifications
        $user->notify(new AppointmentConfirmed($appointment));
        $user->notify(new AppointmentReminder($appointment));

        $count = $this->notificationService->getUnreadCount($user);

        $this->assertEquals(2, $count);
    }

    public function test_notification_service_marks_notification_as_read(): void
    {
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $user->notify(new AppointmentConfirmed($appointment));

        $notification = $user->notifications()->first();
        $this->assertNull($notification->read_at);

        $this->notificationService->markAsRead($notification);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_notification_service_marks_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $user->notify(new AppointmentConfirmed($appointment));
        $user->notify(new AppointmentReminder($appointment));

        $this->assertEquals(2, $user->unreadNotifications()->count());

        $this->notificationService->markAllAsRead($user);

        $user->refresh();
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_notification_service_deletes_notification(): void
    {
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $user->notify(new AppointmentConfirmed($appointment));

        $notification = $user->notifications()->first();
        $notificationId = $notification->id;

        $this->notificationService->deleteNotification($notification);

        $this->assertDatabaseMissing('notifications', ['id' => $notificationId]);
    }

    public function test_notification_service_paginates_notifications(): void
    {
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        for ($i = 0; $i < 20; $i++) {
            $user->notify(new AppointmentConfirmed($appointment));
        }

        $notifications = $this->notificationService->getNotifications($user, 10);

        $this->assertEquals(10, $notifications->perPage());
        $this->assertEquals(20, $notifications->total());
        $this->assertEquals(2, $notifications->lastPage());
    }

    public function test_notification_service_gets_unread_notifications(): void
    {
        $user = User::factory()->create();
        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $user->notify(new AppointmentConfirmed($appointment));
        $user->notify(new AppointmentReminder($appointment));

        // Mark one as read
        $user->notifications()->first()->markAsRead();

        $unread = $this->notificationService->getUnreadNotifications($user);

        $this->assertCount(1, $unread);
    }

    public function test_notifications_use_database_channel(): void
    {
        $appointment = Appointment::factory()->create();

        $notification = new AppointmentConfirmed($appointment);
        $channels = $notification->via($appointment->patient);

        $this->assertEquals(['database'], $channels);
    }

    public function test_send_appointment_reminders_for_tomorrow(): void
    {
        Notification::fake();

        // Create appointments for tomorrow
        $tomorrow = now()->addDay()->toDateString();
        Appointment::factory()->count(3)->create([
            'appointment_date' => $tomorrow,
            'status' => \App\Enums\AppointmentStatus::CONFIRMED,
        ]);

        // Create appointment for today (should not get reminder)
        Appointment::factory()->create([
            'appointment_date' => now()->toDateString(),
            'status' => \App\Enums\AppointmentStatus::CONFIRMED,
        ]);

        $count = $this->notificationService->sendAppointmentReminders();

        $this->assertEquals(3, $count);
        Notification::assertSentTimes(AppointmentReminder::class, 3);
    }
}
