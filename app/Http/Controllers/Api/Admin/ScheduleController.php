<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use App\Services\SlotGeneratorService;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    public function __construct(
        protected SlotGeneratorService $slotService
    ) {
    }
    /**
     * List all schedules.
     */
    public function index(): JsonResponse
    {
        $schedules = Schedule::orderBy('day_of_week')->get();

        return response()->json([
            'success' => true,
            'data' => ScheduleResource::collection($schedules),
        ]);
    }

    /**
     * Create a new schedule.
     */
    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $schedule = Schedule::create($request->validated());

        // Invalidate slot cache when schedule changes
        $this->slotService->invalidateCache();

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الجدول بنجاح.',
            'data' => new ScheduleResource($schedule),
        ], 201);
    }

    /**
     * Get a specific schedule.
     */
    public function show(Schedule $schedule): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ScheduleResource($schedule),
        ]);
    }

    /**
     * Update a schedule.
     */
    public function update(UpdateScheduleRequest $request, Schedule $schedule): JsonResponse
    {
        $schedule->update($request->validated());

        // Invalidate slot cache when schedule changes
        $this->slotService->invalidateCache();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الجدول بنجاح.',
            'data' => new ScheduleResource($schedule->fresh()),
        ]);
    }

    /**
     * Delete a schedule.
     */
    public function destroy(Schedule $schedule): JsonResponse
    {
        $schedule->delete();

        // Invalidate slot cache when schedule changes
        $this->slotService->invalidateCache();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الجدول بنجاح.',
        ]);
    }

    /**
     * Toggle schedule active status.
     */
    public function toggle(Schedule $schedule): JsonResponse
    {
        $schedule->update(['is_active' => !$schedule->is_active]);

        // Invalidate slot cache when schedule changes
        $this->slotService->invalidateCache();

        $status = $schedule->is_active ? 'تفعيل' : 'تعطيل';

        return response()->json([
            'success' => true,
            'message' => "تم {$status} الجدول بنجاح.",
            'data' => new ScheduleResource($schedule->fresh()),
        ]);
    }
}
