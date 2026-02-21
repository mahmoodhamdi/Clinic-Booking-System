<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use App\Services\PrescriptionPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $prescriptions = Prescription::with(['medicalRecord', 'items'])
            ->forPatient($request->user()->id)
            ->when($request->status === 'valid', fn ($q) => $q->valid())
            ->when($request->status === 'expired', fn ($q) => $q->expired())
            ->latest()
            ->paginate($request->per_page ?? 15);

        return ApiResponse::paginated($prescriptions, PrescriptionResource::class);
    }

    public function show(Request $request, Prescription $prescription): JsonResponse
    {
        $this->authorize('view', $prescription);

        $prescription->load(['medicalRecord', 'items']);

        return ApiResponse::success(new PrescriptionResource($prescription));
    }

    public function downloadPdf(Request $request, Prescription $prescription, PrescriptionPdfService $pdfService)
    {
        $this->authorize('view', $prescription);

        return $pdfService->download($prescription);
    }
}
