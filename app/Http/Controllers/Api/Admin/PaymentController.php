<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Http\Requests\Admin\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Appointment;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
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

        $perPage = $request->per_page ?? 15;
        $payments = $query->paginate($perPage);

        return PaymentResource::collection($payments);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $appointment = Appointment::findOrFail($request->appointment_id);

        // Check if appointment already has a payment
        $existingPayment = $appointment->payment;
        if ($existingPayment) {
            return response()->json([
                'message' => 'يوجد دفعة مسجلة لهذا الموعد بالفعل',
                'data' => new PaymentResource($existingPayment),
            ], 422);
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

        return response()->json([
            'message' => 'تم تسجيل الدفعة بنجاح',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    public function show(Payment $payment): PaymentResource
    {
        $payment->load(['appointment.patient']);

        return new PaymentResource($payment);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        if ($payment->isPaid()) {
            return response()->json([
                'message' => 'لا يمكن تعديل دفعة تمت بالفعل',
            ], 422);
        }

        if ($payment->isRefunded()) {
            return response()->json([
                'message' => 'لا يمكن تعديل دفعة مستردة',
            ], 422);
        }

        $payment = $this->paymentService->updatePayment($payment, $request->validated());

        $payment->load(['appointment.patient']);

        return response()->json([
            'message' => 'تم تحديث الدفعة بنجاح',
            'data' => new PaymentResource($payment),
        ]);
    }

    public function markAsPaid(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->isPaid()) {
            return response()->json([
                'message' => 'الدفعة مدفوعة بالفعل',
            ], 422);
        }

        if ($payment->isRefunded()) {
            return response()->json([
                'message' => 'لا يمكن تعديل دفعة مستردة',
            ], 422);
        }

        $this->paymentService->markAsPaid($payment, $request->transaction_id);

        $payment->load(['appointment.patient']);

        return response()->json([
            'message' => 'تم تأكيد الدفع بنجاح',
            'data' => new PaymentResource($payment),
        ]);
    }

    public function refund(Request $request, Payment $payment): JsonResponse
    {
        // Only admin can refund payments
        $this->authorize('refund', $payment);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (!$payment->isPaid()) {
            return response()->json([
                'message' => 'لا يمكن استرداد دفعة غير مدفوعة',
            ], 422);
        }

        $this->paymentService->refund($payment, $request->reason);

        $payment->load(['appointment.patient']);

        return response()->json([
            'message' => 'تم استرداد الدفعة بنجاح',
            'data' => new PaymentResource($payment),
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $from = $request->from ?? now()->startOfMonth()->toDateTimeString();
        $to = $request->to ?? now()->endOfMonth()->toDateTimeString();

        $statistics = $this->paymentService->getStatistics($from, $to);

        return response()->json([
            'data' => $statistics,
        ]);
    }

    public function report(Request $request): JsonResponse
    {
        $period = $request->period ?? 'month';
        $year = $request->year ?? now()->year;

        $report = $this->paymentService->getRevenueReport($period, $year);

        return response()->json([
            'data' => $report,
        ]);
    }

    public function todayStatistics(): JsonResponse
    {
        $statistics = $this->paymentService->getTodayStatistics();

        return response()->json([
            'data' => $statistics,
        ]);
    }

    public function byAppointment(Appointment $appointment): JsonResponse
    {
        $payment = $appointment->payment;

        if (!$payment) {
            return response()->json([
                'message' => 'لا توجد دفعة لهذا الموعد',
            ], 404);
        }

        $payment->load(['appointment.patient']);

        return response()->json([
            'data' => new PaymentResource($payment),
        ]);
    }
}
