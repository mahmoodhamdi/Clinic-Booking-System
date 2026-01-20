<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ClinicSetting::factory()->create();
    }

    // ==================== Record Payment Tests ====================

    /** @test */
    public function admin_can_record_direct_payment(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => $patient->id,
            'amount' => 150.00,
            'payment_method' => 'cash',
            'notes' => 'Payment for consultation',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('payments', [
            'amount' => 150.00,
            'method' => PaymentMethod::CASH->value,
            'status' => PaymentStatus::PAID->value,
        ]);
    }

    /** @test */
    public function admin_can_record_payment_with_card(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => $patient->id,
            'amount' => 200.00,
            'payment_method' => 'card',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('payments', [
            'amount' => 200.00,
            'method' => PaymentMethod::CARD->value,
        ]);
    }

    /** @test */
    public function admin_can_record_payment_with_wallet(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => $patient->id,
            'amount' => 300.00,
            'payment_method' => 'wallet',
            'notes' => 'Wallet payment',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'amount' => 300.00,
            'method' => PaymentMethod::WALLET->value,
            'notes' => 'Wallet payment',
        ]);
    }

    /** @test */
    public function record_payment_defaults_to_cash_method(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => $patient->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'amount' => 100.00,
            'method' => PaymentMethod::CASH->value,
        ]);
    }

    /** @test */
    public function record_payment_requires_patient_id(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/payments/record', [
            'amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['patient_id']);
    }

    /** @test */
    public function record_payment_requires_amount(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => $patient->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function record_payment_requires_valid_patient(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => 99999,
            'amount' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['patient_id']);
    }

    /** @test */
    public function record_payment_requires_positive_amount(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => $patient->id,
            'amount' => -50.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function record_payment_validates_payment_method(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => $patient->id,
            'amount' => 100.00,
            'payment_method' => 'invalid_method',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    /** @test */
    public function record_payment_validates_notes_max_length(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => $patient->id,
            'amount' => 100.00,
            'notes' => str_repeat('a', 501),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['notes']);
    }

    /** @test */
    public function secretary_can_record_payment(): void
    {
        $secretary = User::factory()->secretary()->create();
        Sanctum::actingAs($secretary);

        $patient = User::factory()->patient()->create();

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => $patient->id,
            'amount' => 150.00,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function patient_cannot_record_payment(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => $patient->id,
            'amount' => 100.00,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function unauthenticated_user_cannot_record_payment(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => $patient->id,
            'amount' => 100.00,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function recorded_payment_is_marked_as_paid(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();

        $response = $this->postJson('/api/admin/payments/record', [
            'patient_id' => $patient->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(201);

        $payment = Payment::latest()->first();
        $this->assertEquals(PaymentStatus::PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    // ==================== Other Payment Tests ====================

    /** @test */
    public function admin_can_list_payments(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment = Appointment::factory()->create();
        Payment::factory()->count(5)->create([
            'appointment_id' => $appointment->id,
        ]);

        $response = $this->getJson('/api/admin/payments');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function admin_can_filter_payments_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment1 = Appointment::factory()->create();
        $appointment2 = Appointment::factory()->create();

        Payment::factory()->paid()->count(3)->create([
            'appointment_id' => $appointment1->id,
        ]);
        Payment::factory()->pending()->count(2)->create([
            'appointment_id' => $appointment2->id,
        ]);

        $response = $this->getJson('/api/admin/payments?status=paid');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function admin_can_view_single_payment(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment = Appointment::factory()->create();
        $payment = Payment::factory()->create([
            'appointment_id' => $appointment->id,
        ]);

        $response = $this->getJson("/api/admin/payments/{$payment->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $payment->id,
                ],
            ]);
    }

    /** @test */
    public function admin_can_mark_payment_as_paid(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment = Appointment::factory()->create();
        $payment = Payment::factory()->pending()->create([
            'appointment_id' => $appointment->id,
        ]);

        $response = $this->postJson("/api/admin/payments/{$payment->id}/mark-paid");

        $response->assertOk()
            ->assertJson([
                'message' => 'تم تأكيد الدفع بنجاح',
            ]);

        $this->assertEquals(PaymentStatus::PAID, $payment->fresh()->status);
    }

    /** @test */
    public function admin_can_get_payment_statistics(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/payments/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }
}
