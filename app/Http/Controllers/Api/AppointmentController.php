<?php

namespace App\Http\Controllers\Api;

use App\Enums\CancelledBy;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookAppointmentRequest;
use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(protected AppointmentService $appointmentService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $appointments = $this->appointmentService->getPatientAppointments(
            $request->user(),
            $status
        );

        return response()->json([
            'success' => true,
            'data' => AppointmentResource::collection($appointments),
        ]);
    }

    public function upcoming(Request $request): JsonResponse
    {
        $appointments = $this->appointmentService->getPatientUpcomingAppointments(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data' => AppointmentResource::collection($appointments),
        ]);
    }

    public function store(BookAppointmentRequest $request): JsonResponse
    {
        try {
            $datetime = Carbon::parse($request->validated('datetime'));

            $appointment = $this->appointmentService->book(
                $request->user(),
                $datetime,
                $request->validated('notes')
            );

            return response()->json([
                'success' => true,
                'message' => __('تم حجز الموعد بنجاح'),
                'data' => new AppointmentResource($appointment->load('patient')),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        // Ensure patient can only see their own appointments
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => __('غير مصرح لك بعرض هذا الحجز'),
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new AppointmentResource($appointment->load('patient')),
        ]);
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        // Ensure patient can only cancel their own appointments
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => __('غير مصرح لك بإلغاء هذا الحجز'),
            ], 403);
        }

        $canCancel = $this->appointmentService->canCancel($appointment, $request->user());

        if (!$canCancel['can_cancel']) {
            return response()->json([
                'success' => false,
                'message' => $canCancel['reason'],
            ], 422);
        }

        try {
            $appointment = $this->appointmentService->cancel(
                $appointment,
                $request->validated('reason'),
                CancelledBy::PATIENT
            );

            return response()->json([
                'success' => true,
                'message' => __('تم إلغاء الحجز بنجاح'),
                'data' => new AppointmentResource($appointment->load('patient')),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function checkBooking(Request $request): JsonResponse
    {
        $request->validate([
            'datetime' => 'required|date',
        ]);

        $datetime = Carbon::parse($request->datetime);
        $result = $this->appointmentService->canBook($request->user(), $datetime);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
