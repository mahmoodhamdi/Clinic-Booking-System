<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePatientProfileRequest;
use App\Http\Requests\UpdatePatientProfileRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientDashboardResource;
use App\Http\Resources\PatientProfileResource;
use App\Models\PatientProfile;
use App\Services\PatientStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(
        protected PatientStatisticsService $statisticsService
    ) {}
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('profile');

        $upcomingAppointments = $user->appointments()
            ->upcoming()
            ->with('patient')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->limit(5)
            ->get();

        $statistics = $this->statisticsService->getForPatient($user);

        return response()->json([
            'success' => true,
            'data' => new PatientDashboardResource($user, $upcomingAppointments, $statistics),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => __('لم يتم إنشاء الملف الشخصي بعد'),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new PatientProfileResource($profile),
        ]);
    }

    public function createProfile(CreatePatientProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->profile) {
            return response()->json([
                'success' => false,
                'message' => __('الملف الشخصي موجود بالفعل'),
            ], 422);
        }

        $profile = PatientProfile::create([
            'user_id' => $user->id,
            ...$request->validated(),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('تم إنشاء الملف الشخصي بنجاح'),
            'data' => new PatientProfileResource($profile),
        ], 201);
    }

    public function updateProfile(UpdatePatientProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            // Create profile if it doesn't exist
            $profile = PatientProfile::create([
                'user_id' => $user->id,
                ...$request->validated(),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('تم إنشاء الملف الشخصي بنجاح'),
                'data' => new PatientProfileResource($profile),
            ], 201);
        }

        $profile->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('تم تحديث الملف الشخصي بنجاح'),
            'data' => new PatientProfileResource($profile->fresh()),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status');
        $perPage = $request->integer('per_page', 15);

        $query = $user->appointments()
            ->with('patient')
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $appointments = $query->paginate($perPage);

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

    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();
        $statistics = $this->statisticsService->getForPatient($user);

        return response()->json([
            'success' => true,
            'data' => [
                'total_appointments' => $statistics['total_appointments'],
                'completed_appointments' => $statistics['completed_appointments'],
                'cancelled_appointments' => $statistics['cancelled_appointments'],
                'no_shows' => $statistics['no_show_count'],
                'upcoming_appointments' => $statistics['upcoming_appointments'],
                'last_visit' => $statistics['last_visit'],
                'profile_complete' => $user->has_complete_profile,
            ],
        ]);
    }
}
