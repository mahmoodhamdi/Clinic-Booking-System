<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVacationRequest;
use App\Http\Requests\UpdateVacationRequest;
use App\Http\Resources\VacationResource;
use App\Models\Vacation;
use App\Services\SlotGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacationController extends Controller
{
    public function __construct(
        protected SlotGeneratorService $slotService
    ) {
    }
    /**
     * List all vacations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vacation::orderBy('start_date', 'desc');

        // Filter by status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'active':
                    $query->active();
                    break;
                case 'past':
                    $query->where('end_date', '<', now()->toDateString());
                    break;
            }
        }

        $vacations = $query->get();

        return response()->json([
            'success' => true,
            'data' => VacationResource::collection($vacations),
        ]);
    }

    /**
     * Create a new vacation.
     */
    public function store(StoreVacationRequest $request): JsonResponse
    {
        $vacation = Vacation::create($request->validated());

        // Invalidate slot cache when vacation changes
        $this->slotService->invalidateCache();

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الإجازة بنجاح.',
            'data' => new VacationResource($vacation),
        ], 201);
    }

    /**
     * Get a specific vacation.
     */
    public function show(Vacation $vacation): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new VacationResource($vacation),
        ]);
    }

    /**
     * Update a vacation.
     */
    public function update(UpdateVacationRequest $request, Vacation $vacation): JsonResponse
    {
        $vacation->update($request->validated());

        // Invalidate slot cache when vacation changes
        $this->slotService->invalidateCache();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإجازة بنجاح.',
            'data' => new VacationResource($vacation->fresh()),
        ]);
    }

    /**
     * Delete a vacation.
     */
    public function destroy(Vacation $vacation): JsonResponse
    {
        $vacation->delete();

        // Invalidate slot cache when vacation changes
        $this->slotService->invalidateCache();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإجازة بنجاح.',
        ]);
    }
}
