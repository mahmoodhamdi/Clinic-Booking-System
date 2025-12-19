<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->patient = User::factory()->create();
    }

    public function test_admin_can_list_payments(): void
    {
        Payment::factory()->count(5)->create();

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/payments');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'appointment_id',
                        'amount',
                        'discount',
                        'total',
                        'method',
                        'status',
                        'paid_at',
                        'formatted_amount',
                        'formatted_total',
                    ],
                ],
                'meta',
            ]);
    }

    public function test_admin_can_filter_payments_by_status(): void
    {
        Payment::factory()->count(3)->paid()->create();
        Payment::factory()->count(2)->pending()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/payments?status=paid');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_filter_payments_by_method(): void
    {
        Payment::factory()->count(3)->cash()->create();
        Payment::factory()->count(2)->card()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/payments?method=cash');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_filter_payments_by_date_range(): void
    {
        Payment::factory()->create(['paid_at' => now()->subDays(10)]);
        Payment::factory()->create(['paid_at' => now()->subDays(2)]);
        Payment::factory()->create(['paid_at' => now()]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/payments?' . http_build_query([
            'from_date' => now()->subDays(5)->toDateString(),
            'to_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_create_payment(): void
    {
        $appointment = Appointment::factory()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/payments', [
            'appointment_id' => $appointment->id,
            'amount' => 150.00,
            'discount' => 15.00,
            'method' => PaymentMethod::CASH->value,
            'notes' => 'Test payment',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.appointment_id', $appointment->id);

        $this->assertEquals(150.00, $response->json('data.amount'));
        $this->assertEquals(15.00, $response->json('data.discount'));
        $this->assertEquals(135.00, $response->json('data.total'));

        $this->assertDatabaseHas('payments', [
            'appointment_id' => $appointment->id,
            'amount' => 150.00,
            'discount' => 15.00,
            'total' => 135.00,
        ]);
    }

    public function test_admin_can_create_payment_without_discount(): void
    {
        $appointment = Appointment::factory()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/payments', [
            'appointment_id' => $appointment->id,
            'amount' => 200.00,
            'method' => PaymentMethod::CARD->value,
        ]);

        $response->assertCreated();
        $this->assertEquals(200.00, $response->json('data.total'));
    }

    public function test_admin_can_show_payment(): void
    {
        $payment = Payment::factory()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/admin/payments/{$payment->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $payment->id);
    }

    public function test_admin_can_update_payment(): void
    {
        $payment = Payment::factory()->pending()->create([
            'amount' => 100.00,
            'discount' => 0.00,
            'total' => 100.00,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/admin/payments/{$payment->id}", [
            'amount' => 150.00,
            'discount' => 25.00,
            'method' => PaymentMethod::WALLET->value,
            'notes' => 'Updated payment',
        ]);

        $response->assertOk();
        $this->assertEquals(150.00, $response->json('data.amount'));
        $this->assertEquals(25.00, $response->json('data.discount'));
        $this->assertEquals(125.00, $response->json('data.total'));
    }

    public function test_cannot_update_paid_payment(): void
    {
        $payment = Payment::factory()->paid()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/admin/payments/{$payment->id}", [
            'amount' => 200.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'لا يمكن تعديل دفعة تمت بالفعل');
    }

    public function test_admin_can_mark_payment_as_paid(): void
    {
        $payment = Payment::factory()->pending()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/admin/payments/{$payment->id}/mark-paid");

        $response->assertOk()
            ->assertJsonPath('data.status', PaymentStatus::PAID->value);

        $this->assertNotNull($payment->fresh()->paid_at);
    }

    public function test_cannot_mark_already_paid_payment(): void
    {
        $payment = Payment::factory()->paid()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/admin/payments/{$payment->id}/mark-paid");

        $response->assertStatus(422);
    }

    public function test_admin_can_refund_payment(): void
    {
        $payment = Payment::factory()->paid()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/admin/payments/{$payment->id}/refund", [
            'reason' => 'Customer requested refund',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', PaymentStatus::REFUNDED->value);

        $payment->refresh();
        $this->assertNotNull($payment->refunded_at);
        $this->assertEquals('Customer requested refund', $payment->notes);
    }

    public function test_cannot_refund_pending_payment(): void
    {
        $payment = Payment::factory()->pending()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/admin/payments/{$payment->id}/refund");

        $response->assertStatus(422);
    }

    public function test_admin_can_get_payment_statistics(): void
    {
        Payment::factory()->count(5)->paid()->create(['total' => 100.00]);
        Payment::factory()->count(2)->pending()->create(['total' => 50.00]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/payments/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_revenue',
                    'total_pending',
                    'total_refunded',
                    'net_revenue',
                    'total_payments',
                    'paid_count',
                    'pending_count',
                    'refunded_count',
                    'by_method',
                    'period',
                ],
            ]);
    }

    public function test_admin_can_get_revenue_report(): void
    {
        Payment::factory()->paid()->create([
            'total' => 100.00,
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/payments/report?period=month');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'year',
                    'data',
                    'total',
                ],
            ]);
    }

    public function test_admin_can_get_payment_by_appointment(): void
    {
        $appointment = Appointment::factory()->create();
        $payment = Payment::factory()->create(['appointment_id' => $appointment->id]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/admin/appointments/{$appointment->id}/payment");

        $response->assertOk()
            ->assertJsonPath('data.id', $payment->id);
    }

    public function test_patient_cannot_access_admin_payment_routes(): void
    {
        Sanctum::actingAs($this->patient);

        $this->getJson('/api/admin/payments')->assertForbidden();
        $this->postJson('/api/admin/payments', [])->assertForbidden();
        $this->getJson('/api/admin/payments/statistics')->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_payment_routes(): void
    {
        $this->getJson('/api/admin/payments')->assertUnauthorized();
        $this->postJson('/api/admin/payments', [])->assertUnauthorized();
    }

    public function test_payment_validation_requires_appointment_id(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/payments', [
            'amount' => 100.00,
            'method' => PaymentMethod::CASH->value,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['appointment_id']);
    }

    public function test_payment_validation_requires_amount(): void
    {
        $appointment = Appointment::factory()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/payments', [
            'appointment_id' => $appointment->id,
            'method' => PaymentMethod::CASH->value,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_payment_validation_requires_valid_method(): void
    {
        $appointment = Appointment::factory()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/payments', [
            'appointment_id' => $appointment->id,
            'amount' => 100.00,
            'method' => 'invalid_method',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['method']);
    }

    public function test_payment_amount_must_be_positive(): void
    {
        $appointment = Appointment::factory()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/payments', [
            'appointment_id' => $appointment->id,
            'amount' => -50.00,
            'method' => PaymentMethod::CASH->value,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_discount_cannot_exceed_amount(): void
    {
        $appointment = Appointment::factory()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/payments', [
            'appointment_id' => $appointment->id,
            'amount' => 100.00,
            'discount' => 150.00,
            'method' => PaymentMethod::CASH->value,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount']);
    }
}
