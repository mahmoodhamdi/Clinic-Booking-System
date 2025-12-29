<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SlotGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    protected SlotGeneratorService $slotService;

    public function __construct(SlotGeneratorService $slotService)
    {
        $this->slotService = $slotService;
    }

    /**
     * Get available dates for booking.
     */
    public function dates(Request $request): JsonResponse
    {
        $days = $request->input('days', null);
        $dates = $this->slotService->getAvailableDates($days);

        return response()->json([
            'success' => true,
            'data' => [
                'dates' => $dates,
                'summary' => $this->slotService->getSlotsSummary($days),
            ],
        ]);
    }

    /**
     * Get available slots for a specific date.
     */
    public function slots(Request $request, string $date): JsonResponse
    {
        try {
            $dateObj = Carbon::parse($date);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'تنسيق التاريخ غير صحيح.',
            ], 422);
        }

        // Check if date is in the past
        if ($dateObj->lt(now()->startOfDay())) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن الحجز في تاريخ سابق.',
            ], 422);
        }

        // Check if date is too far in the future
        $settings = $this->slotService->getClinicSettings();
        if ($dateObj->gt($settings->getMaxBookingDate())) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكن الحجز لأكثر من {$settings->advance_booking_days} يوم مقدماً.",
            ], 422);
        }

        $slots = $this->slotService->getSlotsForDate($dateObj);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $dateObj->toDateString(),
                'day_name' => \App\Enums\DayOfWeek::fromDate($dateObj)->labelAr(),
                'slots' => $slots,
                'slots_count' => $slots->count(),
                'is_available' => $slots->isNotEmpty(),
            ],
        ]);
    }

    /**
     * Check if a specific slot is available.
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'datetime' => ['required', 'date'],
        ]);

        try {
            $datetime = Carbon::parse($request->datetime);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'تنسيق التاريخ والوقت غير صحيح.',
            ], 422);
        }

        $isAvailable = $this->slotService->isSlotAvailable($datetime);

        return response()->json([
            'success' => true,
            'data' => [
                'datetime' => $datetime->toIso8601String(),
                'is_available' => $isAvailable,
            ],
        ]);
    }

    /**
     * Get next available slot.
     */
    public function next(): JsonResponse
    {
        $nextSlot = $this->slotService->getNextAvailableSlot();

        if (!$nextSlot) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد مواعيد متاحة حالياً.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $nextSlot,
        ]);
    }
}
