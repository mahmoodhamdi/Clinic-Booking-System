<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\CancelledBy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAppointmentsRequest;
use App\Http\Requests\Admin\UpdateAppointmentNotesRequest;
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

    public function index(ListAppointmentsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = $request->integer('per_page', 15);

        $appointments = $this->appointmentService->getAllAppointments($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => AppointmentResource::collection($appointments),
            'meta' => [
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'per_page' => $appointments->perPage(),
                'total' => $appointments->total(),
            ],
        ]);
    }

    public function today(): JsonResponse
    {
        $appointments = $this->appointmentService->getTodayAppointments();

        return response()->json([
            'success' => true,
            'data' => AppointmentResource::collection($appointments),
            'summary' => [
                'total' => $appointments->count(),
                'pending' => $appointments->where('status.value', 'pending')->count(),
                'confirmed' => $appointments->where('status.value', 'confirmed')->count(),
                'completed' => $appointments->where('status.value', 'completed')->count(),
            ],
        ]);
    }

    public function upcoming(Request $request): JsonResponse
    {
        $days = $request->integer('days', 7);
        $appointments = $this->appointmentService->getUpcomingAppointments($days);

        return response()->json([
            'success' => true,
            'data' => AppointmentResource::collection($appointments),
        ]);
    }

    public function forDate(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($request->date);
        $appointments = $this->appointmentService->getAppointmentsForDate($date);

        return response()->json([
            'success' => true,
            'data' => AppointmentResource::collection($appointments),
            'summary' => [
                'date' => $date->toDateString(),
                'day_name' => $date->locale('ar')->dayName,
                'total' => $appointments->count(),
            ],
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $from = $request->has('from_date') ? Carbon::parse($request->from_date) : null;
        $to = $request->has('to_date') ? Carbon::parse($request->to_date) : null;

        $statistics = $this->appointmentService->getStatistics($from, $to);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new AppointmentResource($appointment->load('patient')),
        ]);
    }

    public function confirm(Appointment $appointment): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->confirm($appointment);

            return response()->json([
                'success' => true,
                'message' => __('تم تأكيد الحجز بنجاح'),
                'data' => new AppointmentResource($appointment->load('patient')),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function complete(Request $request, Appointment $appointment): JsonResponse
    {
        $adminNotes = $request->input('admin_notes');

        try {
            $appointment = $this->appointmentService->complete($appointment, $adminNotes);

            return response()->json([
                'success' => true,
                'message' => __('تم إتمام الحجز بنجاح'),
                'data' => new AppointmentResource($appointment->load('patient')),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->cancel(
                $appointment,
                $request->validated('reason'),
                CancelledBy::ADMIN
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

    public function noShow(Appointment $appointment): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->markNoShow($appointment);

            return response()->json([
                'success' => true,
                'message' => __('تم تسجيل عدم الحضور'),
                'data' => new AppointmentResource($appointment->load('patient')),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateNotes(UpdateAppointmentNotesRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment->update([
            'admin_notes' => $request->validated('admin_notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('تم تحديث الملاحظات بنجاح'),
            'data' => new AppointmentResource($appointment->load('patient')),
        ]);
    }
}
