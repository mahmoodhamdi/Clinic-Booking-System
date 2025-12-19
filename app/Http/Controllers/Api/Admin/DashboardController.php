<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function stats(): JsonResponse
    {
        $statistics = $this->dashboardService->getOverviewStatistics();

        return response()->json([
            'data' => $statistics,
        ]);
    }

    public function today(): JsonResponse
    {
        $statistics = $this->dashboardService->getTodayStatistics();

        return response()->json([
            'data' => $statistics,
        ]);
    }

    public function weekly(): JsonResponse
    {
        $statistics = $this->dashboardService->getWeeklyStatistics();

        return response()->json([
            'data' => $statistics,
        ]);
    }

    public function monthly(Request $request): JsonResponse
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $statistics = $this->dashboardService->getMonthlyStatistics($month, $year);

        return response()->json([
            'data' => $statistics,
        ]);
    }

    public function chart(Request $request): JsonResponse
    {
        $period = $request->period ?? 'week';

        $chartData = $this->dashboardService->getChartData($period);

        return response()->json([
            'data' => $chartData,
        ]);
    }

    public function recentActivity(Request $request): JsonResponse
    {
        $limit = $request->limit ?? 10;

        $activity = $this->dashboardService->getRecentActivity($limit);

        return response()->json([
            'data' => $activity,
        ]);
    }

    public function upcomingAppointments(Request $request): JsonResponse
    {
        $limit = $request->limit ?? 5;

        $appointments = $this->dashboardService->getUpcomingAppointments($limit);

        return response()->json([
            'data' => AppointmentResource::collection($appointments),
        ]);
    }
}
