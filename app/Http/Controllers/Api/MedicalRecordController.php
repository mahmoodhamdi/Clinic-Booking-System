<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MedicalRecordResource;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MedicalRecordController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $records = MedicalRecord::with(['appointment', 'prescriptions'])
            ->forPatient($request->user()->id)
            ->latest()
            ->paginate($request->per_page ?? 15);

        return MedicalRecordResource::collection($records);
    }

    public function show(Request $request, MedicalRecord $medicalRecord): MedicalRecordResource
    {
        $this->authorize('view', $medicalRecord);

        $medicalRecord->load(['appointment', 'prescriptions.items', 'attachments']);

        return new MedicalRecordResource($medicalRecord);
    }
}
