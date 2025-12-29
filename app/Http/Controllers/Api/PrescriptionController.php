<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PrescriptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $prescriptions = Prescription::with(['medicalRecord', 'items'])
            ->forPatient($request->user()->id)
            ->when($request->status === 'valid', fn ($q) => $q->valid())
            ->when($request->status === 'expired', fn ($q) => $q->expired())
            ->latest()
            ->paginate($request->per_page ?? 15);

        return PrescriptionResource::collection($prescriptions);
    }

    public function show(Request $request, Prescription $prescription): PrescriptionResource
    {
        $this->authorize('view', $prescription);

        $prescription->load(['medicalRecord', 'items']);

        return new PrescriptionResource($prescription);
    }
}
