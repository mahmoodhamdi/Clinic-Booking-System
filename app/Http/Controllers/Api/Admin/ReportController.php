<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    public function appointments(Request $request): JsonResponse
    {
        $data = $this->reportService->getAppointmentsReport(
            $request->from_date,
            $request->to_date,
            $request->status
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    public function revenue(Request $request): JsonResponse
    {
        $data = $this->reportService->getRevenueReport(
            $request->from_date,
            $request->to_date,
            $request->group_by ?? 'day'
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    public function patients(Request $request): JsonResponse
    {
        $data = $this->reportService->getPatientsReport(
            $request->from_date,
            $request->to_date
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    public function exportAppointments(Request $request): Response
    {
        $pdf = $this->reportService->exportAppointmentsReportToPdf(
            $request->from_date,
            $request->to_date,
            $request->status
        );

        $filename = 'appointments-report-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportRevenue(Request $request): Response
    {
        $pdf = $this->reportService->exportRevenueReportToPdf(
            $request->from_date,
            $request->to_date
        );

        $filename = 'revenue-report-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportPatients(Request $request): Response
    {
        $pdf = $this->reportService->exportPatientsReportToPdf(
            $request->from_date,
            $request->to_date
        );

        $filename = 'patients-report-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
