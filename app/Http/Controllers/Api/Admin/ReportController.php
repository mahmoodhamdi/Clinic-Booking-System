<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    public function appointments(ReportFilterRequest $request): JsonResponse
    {
        $data = $this->reportService->getAppointmentsReport(
            $request->validated('from_date'),
            $request->validated('to_date'),
            $request->validated('status')
        );

        return ApiResponse::success($data);
    }

    public function revenue(ReportFilterRequest $request): JsonResponse
    {
        $data = $this->reportService->getRevenueReport(
            $request->validated('from_date'),
            $request->validated('to_date'),
            $request->validated('group_by', 'day')
        );

        return ApiResponse::success($data);
    }

    public function patients(ReportFilterRequest $request): JsonResponse
    {
        $data = $this->reportService->getPatientsReport(
            $request->validated('from_date'),
            $request->validated('to_date')
        );

        return ApiResponse::success($data);
    }

    public function exportAppointments(ReportFilterRequest $request): Response
    {
        $pdf = $this->reportService->exportAppointmentsReportToPdf(
            $request->validated('from_date'),
            $request->validated('to_date'),
            $request->validated('status')
        );

        $filename = 'appointments-report-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function exportRevenue(ReportFilterRequest $request): Response
    {
        $pdf = $this->reportService->exportRevenueReportToPdf(
            $request->validated('from_date'),
            $request->validated('to_date')
        );

        $filename = 'revenue-report-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function exportPatients(ReportFilterRequest $request): Response
    {
        $pdf = $this->reportService->exportPatientsReportToPdf(
            $request->validated('from_date'),
            $request->validated('to_date')
        );

        $filename = 'patients-report-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
