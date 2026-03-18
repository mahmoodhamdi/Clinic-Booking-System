<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Admin\RecordPaymentRequest;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Http\Requests\Admin\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Appointment;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['appointment.patient'])
            ->when($request->status, function ($q, $status) {
                if ($status === 'paid') {
                    $q->paid();
                } elseif ($status === 'pending') {
                    $q->pending();
                } elseif ($status === 'refunded') {
                    $q->refunded();
                }
            })
            ->when($request->method, function ($q, $method) {
                $q->where('method', $method);
            })
            ->when($request->patient_id, function ($q, $patientId) {
                $q->forPatient($patientId);
            })
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->forDateRange($request->from_date, $request->to_date);
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('transaction_id', 'like', "%{$search}%")
                        ->orWhereHas('appointment.patient', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest();

        $perPage = min((int) ($request->per_page ?? 15), 100);
        $payments = $query->paginate($perPage);

        return ApiResponse::paginated($payments, PaymentResource::class);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $appointment = Appointment::findOrFail($request->appointment_id);

        // Check if appointment already has a payment
        $existingPayment = $appointment->payment;
        if ($existingPayment) {
            return ApiResponse::error('يوجد دفعة مسجلة لهذا الموعد بالفعل', 422);
        }

        $payment = $this->paymentService->createPayment(
            appointment: $appointment,
            amount: $request->amount,
            method: PaymentMethod::from($request->method),
            discount: $request->discount ?? 0,
            notes: $request->notes
        );

        // Mark as paid if requested
        if ($request->boolean('mark_as_paid')) {
            $this->paymentService->markAsPaid($payment, $request->transaction_id);
        }

        $payment->load(['appointment.patient']);

        return ApiResponse::created(new PaymentResource($payment), 'تم تسجيل الدفعة بنجاح');
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['appointment.patient']);

        return ApiResponse::success(new PaymentResource($payment));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        if ($payment->isPaid()) {
            return ApiResponse::error('لا يمكن تعديل دفعة تمت بالفعل', 422);
        }

        if ($payment->isRefunded()) {
            return ApiResponse::error('لا يمكن تعديل دفعة مستردة', 422);
        }

        $payment = $this->paymentService->updatePayment($payment, $request->validated());

        $payment->load(['appointment.patient']);

        return ApiResponse::success(new PaymentResource($payment), 'تم تحديث الدفعة بنجاح');
    }

    public function markAsPaid(Request $request, Payment $payment): JsonResponse
    {
        $request->validate([
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        if ($payment->isPaid()) {
            return ApiResponse::error('الدفعة مدفوعة بالفعل', 422);
        }

        if ($payment->isRefunded()) {
            return ApiResponse::error('لا يمكن تعديل دفعة مستردة', 422);
        }

        $this->paymentService->markAsPaid($payment, $request->transaction_id);

        $payment->load(['appointment.patient']);

        return ApiResponse::success(new PaymentResource($payment), 'تم تأكيد الدفع بنجاح');
    }

    public function refund(Request $request, Payment $payment): JsonResponse
    {
        // Only admin can refund payments
        $this->authorize('refund', $payment);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $payment->isPaid()) {
            return ApiResponse::error('لا يمكن استرداد دفعة غير مدفوعة', 422);
        }

        $this->paymentService->refund($payment, $request->reason);

        $payment->load(['appointment.patient']);

        return ApiResponse::success(new PaymentResource($payment), 'تم استرداد الدفعة بنجاح');
    }

    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->from ?? now()->startOfMonth()->toDateTimeString();
        $to = $request->to ?? now()->endOfMonth()->toDateTimeString();

        $statistics = $this->paymentService->getStatistics($from, $to);

        return ApiResponse::success($statistics);
    }

    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'period' => ['nullable', 'string', 'in:week,month,quarter,year'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
        ]);

        $period = $request->period ?? 'month';
        $year = (int) ($request->year ?? now()->year);

        $report = $this->paymentService->getRevenueReport($period, $year);

        return ApiResponse::success($report);
    }

    public function todayStatistics(): JsonResponse
    {
        $statistics = $this->paymentService->getTodayStatistics();

        return ApiResponse::success($statistics);
    }

    public function byAppointment(Appointment $appointment): JsonResponse
    {
        $payment = $appointment->payment;

        if (! $payment) {
            return ApiResponse::notFound('لا توجد دفعة لهذا الموعد');
        }

        $payment->load(['appointment.patient']);

        return ApiResponse::success(new PaymentResource($payment));
    }

    /**
     * Record a direct payment for a patient (without appointment).
     */
    public function record(RecordPaymentRequest $request): JsonResponse
    {
        $amount = $request->amount;
        $payment = Payment::create([
            'appointment_id' => null,
            'patient_id' => $request->patient_id,
            'amount' => $amount,
            'discount' => 0,
            'total' => $amount,
            'method' => $request->payment_method ? PaymentMethod::from($request->payment_method) : PaymentMethod::CASH,
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
            'notes' => $request->notes,
        ]);

        return ApiResponse::created(new PaymentResource($payment), 'تم تسجيل الدفعة بنجاح');
    }
}
