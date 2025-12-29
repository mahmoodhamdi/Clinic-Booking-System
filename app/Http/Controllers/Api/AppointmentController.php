<?php

namespace App\Http\Controllers\Api;

use App\Enums\CancelledBy;
use App\Exceptions\BusinessLogicException;
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
        } catch (BusinessLogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
            ], $e->getCode());
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);

        return response()->json([
            'success' => true,
            'data' => new AppointmentResource($appointment->load('patient')),
        ]);
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        // Authorization handled via policy - check if user can cancel
        $this->authorize('cancel', $appointment);

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
        } catch (BusinessLogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
            ], $e->getCode());
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
