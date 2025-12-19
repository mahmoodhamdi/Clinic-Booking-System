<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePrescriptionRequest;
use App\Http\Requests\Admin\UpdatePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\PrescriptionPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Prescription::with(['medicalRecord.patient', 'items'])
            ->when($request->patient_id, fn ($q, $patientId) => $q->forPatient($patientId))
            ->when($request->dispensed === 'true', fn ($q) => $q->dispensed())
            ->when($request->dispensed === 'false', fn ($q) => $q->notDispensed())
            ->when($request->status === 'valid', fn ($q) => $q->valid())
            ->when($request->status === 'expired', fn ($q) => $q->expired())
            ->when($request->search, function ($q, $search) {
                $q->where('prescription_number', 'like', "%{$search}%");
            })
            ->latest();

        $perPage = $request->per_page ?? 15;
        $prescriptions = $query->paginate($perPage);

        return PrescriptionResource::collection($prescriptions);
    }

    public function store(StorePrescriptionRequest $request): JsonResponse
    {
        $prescription = DB::transaction(function () use ($request) {
            $prescription = Prescription::create([
                'medical_record_id' => $request->medical_record_id,
                'notes' => $request->notes,
                'valid_until' => $request->valid_until,
            ]);

            foreach ($request->items as $itemData) {
                $prescription->items()->create([
                    'medication_name' => $itemData['medication_name'],
                    'dosage' => $itemData['dosage'],
                    'frequency' => $itemData['frequency'],
                    'duration' => $itemData['duration'],
                    'instructions' => $itemData['instructions'] ?? null,
                    'quantity' => $itemData['quantity'] ?? null,
                ]);
            }

            return $prescription;
        });

        $prescription->load(['medicalRecord.patient', 'items']);

        return response()->json([
            'message' => 'تم إنشاء الوصفة الطبية بنجاح',
            'data' => new PrescriptionResource($prescription),
        ], 201);
    }

    public function show(Prescription $prescription): PrescriptionResource
    {
        $prescription->load(['medicalRecord.patient', 'medicalRecord.appointment', 'items']);

        return new PrescriptionResource($prescription);
    }

    public function update(UpdatePrescriptionRequest $request, Prescription $prescription): JsonResponse
    {
        DB::transaction(function () use ($request, $prescription) {
            $prescription->update([
                'notes' => $request->notes ?? $prescription->notes,
                'valid_until' => $request->valid_until ?? $prescription->valid_until,
            ]);

            if ($request->has('items')) {
                // Get existing item IDs
                $existingIds = collect($request->items)
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                // Delete items not in the request
                $prescription->items()->whereNotIn('id', $existingIds)->delete();

                foreach ($request->items as $itemData) {
                    if (isset($itemData['id'])) {
                        // Update existing item
                        PrescriptionItem::where('id', $itemData['id'])->update([
                            'medication_name' => $itemData['medication_name'],
                            'dosage' => $itemData['dosage'],
                            'frequency' => $itemData['frequency'],
                            'duration' => $itemData['duration'],
                            'instructions' => $itemData['instructions'] ?? null,
                            'quantity' => $itemData['quantity'] ?? null,
                        ]);
                    } else {
                        // Create new item
                        $prescription->items()->create([
                            'medication_name' => $itemData['medication_name'],
                            'dosage' => $itemData['dosage'],
                            'frequency' => $itemData['frequency'],
                            'duration' => $itemData['duration'],
                            'instructions' => $itemData['instructions'] ?? null,
                            'quantity' => $itemData['quantity'] ?? null,
                        ]);
                    }
                }
            }
        });

        $prescription->load(['medicalRecord.patient', 'items']);

        return response()->json([
            'message' => 'تم تحديث الوصفة الطبية بنجاح',
            'data' => new PrescriptionResource($prescription),
        ]);
    }

    public function destroy(Prescription $prescription): JsonResponse
    {
        $prescription->delete();

        return response()->json([
            'message' => 'تم حذف الوصفة الطبية بنجاح',
        ]);
    }

    public function markAsDispensed(Prescription $prescription): JsonResponse
    {
        $prescription->markAsDispensed();

        $prescription->load(['medicalRecord.patient', 'items']);

        return response()->json([
            'message' => 'تم تحديث حالة الصرف بنجاح',
            'data' => new PrescriptionResource($prescription),
        ]);
    }

    public function byPatient(int $patientId): AnonymousResourceCollection
    {
        $prescriptions = Prescription::with(['medicalRecord', 'items'])
            ->forPatient($patientId)
            ->latest()
            ->get();

        return PrescriptionResource::collection($prescriptions);
    }

    public function downloadPdf(Prescription $prescription, PrescriptionPdfService $pdfService)
    {
        return $pdfService->download($prescription);
    }

    public function streamPdf(Prescription $prescription, PrescriptionPdfService $pdfService)
    {
        return $pdfService->stream($prescription);
    }
}
