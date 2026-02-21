<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Admin\StoreMedicalRecordRequest;
use App\Http\Requests\Admin\UpdateMedicalRecordRequest;
use App\Http\Resources\MedicalRecordResource;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MedicalRecord::with(['patient', 'appointment'])
            ->when($request->patient_id, fn ($q) => $q->forPatient($request->patient_id))
            ->when($request->has('has_follow_up'), fn ($q) => $q->withFollowUp())
            ->when($request->follow_up_due, fn ($q) => $q->followUpDue())
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('diagnosis', 'like', "%{$search}%")
                        ->orWhere('symptoms', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest();

        $perPage = $request->per_page ?? 15;
        $records = $query->paginate($perPage);

        return ApiResponse::paginated($records, MedicalRecordResource::class);
    }

    public function store(StoreMedicalRecordRequest $request): JsonResponse
    {
        $appointment = Appointment::findOrFail($request->appointment_id);

        $medicalRecord = MedicalRecord::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->user_id,
            'diagnosis' => $request->diagnosis,
            'symptoms' => $request->symptoms,
            'examination_notes' => $request->examination_notes,
            'treatment_plan' => $request->treatment_plan,
            'follow_up_date' => $request->follow_up_date,
            'follow_up_notes' => $request->follow_up_notes,
            'vital_signs' => $request->vital_signs,
        ]);

        $medicalRecord->load(['patient', 'appointment']);

        return ApiResponse::created(new MedicalRecordResource($medicalRecord), 'تم إنشاء السجل الطبي بنجاح');
    }

    public function show(MedicalRecord $medicalRecord): JsonResponse
    {
        $medicalRecord->load(['patient', 'appointment', 'prescriptions.items', 'attachments.uploader']);

        return ApiResponse::success(new MedicalRecordResource($medicalRecord));
    }

    public function update(UpdateMedicalRecordRequest $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $medicalRecord->update($request->validated());

        $medicalRecord->load(['patient', 'appointment']);

        return ApiResponse::success(new MedicalRecordResource($medicalRecord), 'تم تحديث السجل الطبي بنجاح');
    }

    public function destroy(MedicalRecord $medicalRecord): JsonResponse
    {
        // Only admin can delete medical records
        $this->authorize('delete', $medicalRecord);

        // Delete associated attachments files
        foreach ($medicalRecord->attachments as $attachment) {
            $attachment->deleteFile();
        }

        $medicalRecord->delete();

        return ApiResponse::success(null, 'تم حذف السجل الطبي بنجاح');
    }

    public function byPatient(int $patientId): JsonResponse
    {
        $records = MedicalRecord::with(['appointment', 'prescriptions'])
            ->forPatient($patientId)
            ->latest()
            ->get();

        return ApiResponse::success(MedicalRecordResource::collection($records));
    }

    public function followUpsDue(): JsonResponse
    {
        $records = MedicalRecord::with(['patient', 'appointment'])
            ->followUpDue()
            ->orderBy('follow_up_date')
            ->get();

        return ApiResponse::success(MedicalRecordResource::collection($records));
    }
}
