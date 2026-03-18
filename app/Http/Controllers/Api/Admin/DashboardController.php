<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
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

        return ApiResponse::success($statistics);
    }

    public function today(): JsonResponse
    {
        $statistics = $this->dashboardService->getTodayStatistics();

        return ApiResponse::success($statistics);
    }

    public function weekly(): JsonResponse
    {
        $statistics = $this->dashboardService->getWeeklyStatistics();

        return ApiResponse::success($statistics);
    }

    public function monthly(Request $request): JsonResponse
    {
        $month = (int) ($request->month ?? now()->month);
        $year = (int) ($request->year ?? now()->year);

        $statistics = $this->dashboardService->getMonthlyStatistics($month, $year);

        return ApiResponse::success($statistics);
    }

    public function chart(Request $request): JsonResponse
    {
        $period = (string) ($request->period ?? 'week');

        $chartData = $this->dashboardService->getChartData($period);

        return ApiResponse::success($chartData);
    }

    public function recentActivity(Request $request): JsonResponse
    {
        $limit = min((int) ($request->limit ?? 10), 50);

        $activity = $this->dashboardService->getRecentActivity($limit);

        return ApiResponse::success($activity);
    }

    public function upcomingAppointments(Request $request): JsonResponse
    {
        $limit = min((int) ($request->limit ?? 5), 50);

        $appointments = $this->dashboardService->getUpcomingAppointments($limit);

        return ApiResponse::success(AppointmentResource::collection($appointments));
    }
}
