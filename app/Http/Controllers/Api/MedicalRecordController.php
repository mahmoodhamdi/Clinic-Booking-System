<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Resources\MedicalRecordResource;
use App\Models\MedicalRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $records = MedicalRecord::with(['appointment', 'prescriptions'])
            ->forPatient($request->user()->id)
            ->latest()
            ->paginate(min((int) ($request->per_page ?? 15), 100));

        return ApiResponse::paginated($records, MedicalRecordResource::class);
    }

    public function show(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $this->authorize('view', $medicalRecord);

        $medicalRecord->load(['appointment', 'prescriptions.items', 'attachments']);

        return ApiResponse::success(new MedicalRecordResource($medicalRecord));
    }
}
