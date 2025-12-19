<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListPatientsRequest;
use App\Http\Requests\UpdatePatientProfileRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(ListPatientsRequest $request): JsonResponse
    {
        $query = User::patients()
            ->with('profile');

        // Search by name, phone, or email
        if ($search = $request->validated('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('status')) {
            $isActive = $request->validated('status') === 'active';
            $query->where('is_active', $isActive);
        }

        // Filter by has profile
        if ($request->has('has_profile')) {
            $hasProfile = filter_var($request->validated('has_profile'), FILTER_VALIDATE_BOOLEAN);
            if ($hasProfile) {
                $query->whereHas('profile');
            } else {
                $query->whereDoesntHave('profile');
            }
        }

        // Filter by blood type
        if ($bloodType = $request->validated('blood_type')) {
            $query->whereHas('profile', function ($q) use ($bloodType) {
                $q->where('blood_type', $bloodType);
            });
        }

        // Ordering
        $orderBy = $request->validated('order_by') ?? 'created_at';
        $orderDir = $request->validated('order_dir') ?? 'desc';

        if ($orderBy === 'appointments_count') {
            $query->withCount('appointments')
                ->orderBy('appointments_count', $orderDir);
        } else {
            $query->orderBy($orderBy, $orderDir);
        }

        $perPage = $request->integer('per_page', 15);
        $patients = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PatientResource::collection($patients),
            'meta' => [
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'per_page' => $patients->perPage(),
                'total' => $patients->total(),
            ],
        ]);
    }

    public function show(User $patient): JsonResponse
    {
        if ($patient->role !== UserRole::PATIENT) {
            return response()->json([
                'success' => false,
                'message' => __('هذا المستخدم ليس مريضاً'),
            ], 404);
        }

        $patient->load('profile');

        return response()->json([
            'success' => true,
            'data' => new PatientResource($patient),
        ]);
    }

    public function appointments(Request $request, User $patient): JsonResponse
    {
        if ($patient->role !== UserRole::PATIENT) {
            return response()->json([
                'success' => false,
                'message' => __('هذا المستخدم ليس مريضاً'),
            ], 404);
        }

        $status = $request->query('status');
        $perPage = $request->integer('per_page', 15);

        $query = $patient->appointments()
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

    public function statistics(User $patient): JsonResponse
    {
        if ($patient->role !== UserRole::PATIENT) {
            return response()->json([
                'success' => false,
                'message' => __('هذا المستخدم ليس مريضاً'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_appointments' => $patient->total_appointments,
                'completed_appointments' => $patient->completed_appointments_count,
                'cancelled_appointments' => $patient->cancelled_appointments_count,
                'no_shows' => $patient->no_show_count,
                'upcoming_appointments' => $patient->upcoming_appointments_count,
                'last_visit' => $patient->last_visit?->toDateString(),
                'has_profile' => $patient->profile !== null,
                'profile_complete' => $patient->has_complete_profile,
            ],
        ]);
    }

    public function updateProfile(UpdatePatientProfileRequest $request, User $patient): JsonResponse
    {
        if ($patient->role !== UserRole::PATIENT) {
            return response()->json([
                'success' => false,
                'message' => __('هذا المستخدم ليس مريضاً'),
            ], 404);
        }

        $profile = $patient->profile;

        if (!$profile) {
            $profile = $patient->profile()->create($request->validated());
        } else {
            $profile->update($request->validated());
        }

        return response()->json([
            'success' => true,
            'message' => __('تم تحديث الملف الشخصي بنجاح'),
            'data' => new PatientResource($patient->load('profile')),
        ]);
    }

    public function toggleStatus(User $patient): JsonResponse
    {
        if ($patient->role !== UserRole::PATIENT) {
            return response()->json([
                'success' => false,
                'message' => __('هذا المستخدم ليس مريضاً'),
            ], 404);
        }

        $patient->update([
            'is_active' => !$patient->is_active,
        ]);

        $statusMessage = $patient->is_active
            ? __('تم تفعيل حساب المريض')
            : __('تم تعطيل حساب المريض');

        return response()->json([
            'success' => true,
            'message' => $statusMessage,
            'data' => new PatientResource($patient->load('profile')),
        ]);
    }

    public function addNotes(Request $request, User $patient): JsonResponse
    {
        if ($patient->role !== UserRole::PATIENT) {
            return response()->json([
                'success' => false,
                'message' => __('هذا المستخدم ليس مريضاً'),
            ], 404);
        }

        $request->validate([
            'medical_notes' => 'required|string|max:2000',
        ]);

        $profile = $patient->profile;

        if (!$profile) {
            $profile = $patient->profile()->create([
                'medical_notes' => $request->medical_notes,
            ]);
        } else {
            $existingNotes = $profile->medical_notes ?? '';
            $newNotes = $existingNotes
                ? $existingNotes . "\n\n---\n\n" . now()->format('Y-m-d H:i') . ":\n" . $request->medical_notes
                : $request->medical_notes;

            $profile->update(['medical_notes' => $newNotes]);
        }

        return response()->json([
            'success' => true,
            'message' => __('تم إضافة الملاحظات بنجاح'),
            'data' => new PatientResource($patient->load('profile')),
        ]);
    }

    public function summary(): JsonResponse
    {
        $totalPatients = User::patients()->count();
        $activePatients = User::patients()->active()->count();
        $withProfile = User::patients()->whereHas('profile')->count();
        $newThisMonth = User::patients()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_patients' => $totalPatients,
                'active_patients' => $activePatients,
                'inactive_patients' => $totalPatients - $activePatients,
                'with_profile' => $withProfile,
                'without_profile' => $totalPatients - $withProfile,
                'new_this_month' => $newThisMonth,
            ],
        ]);
    }
}
