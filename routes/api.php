<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\SlotController;
use App\Http\Controllers\Api\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Api\Admin\AttachmentController;
use App\Http\Controllers\Api\Admin\ClinicSettingController;
use App\Http\Controllers\Api\Admin\MedicalRecordController as AdminMedicalRecordController;
use App\Http\Controllers\Api\Admin\PatientController as AdminPatientController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\PaymentController;
use App\Http\Controllers\Api\Admin\PrescriptionController as AdminPrescriptionController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\ScheduleController;
use App\Http\Controllers\Api\Admin\VacationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Protected auth routes
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/avatar', [AuthController::class, 'uploadAvatar']);
    Route::delete('/account', [AuthController::class, 'deleteAccount']);
});

// Public slots routes
Route::prefix('slots')->group(function () {
    Route::get('/dates', [SlotController::class, 'dates']);
    Route::get('/next', [SlotController::class, 'next']);
    Route::post('/check', [SlotController::class, 'check']);
    Route::get('/{date}', [SlotController::class, 'slots']);
});

// Patient appointment routes (requires authentication)
Route::middleware('auth:sanctum')->prefix('appointments')->group(function () {
    Route::get('/', [AppointmentController::class, 'index']);
    Route::get('/upcoming', [AppointmentController::class, 'upcoming']);
    Route::post('/', [AppointmentController::class, 'store']);
    Route::post('/check', [AppointmentController::class, 'checkBooking']);
    Route::get('/{appointment}', [AppointmentController::class, 'show']);
    Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel']);
});

// Patient profile & dashboard routes (requires authentication)
Route::middleware('auth:sanctum')->prefix('patient')->group(function () {
    Route::get('/dashboard', [PatientController::class, 'dashboard']);
    Route::get('/profile', [PatientController::class, 'profile']);
    Route::post('/profile', [PatientController::class, 'createProfile']);
    Route::put('/profile', [PatientController::class, 'updateProfile']);
    Route::get('/history', [PatientController::class, 'history']);
    Route::get('/statistics', [PatientController::class, 'statistics']);
});

// Patient medical records routes (requires authentication)
Route::middleware('auth:sanctum')->prefix('medical-records')->group(function () {
    Route::get('/', [MedicalRecordController::class, 'index']);
    Route::get('/{medicalRecord}', [MedicalRecordController::class, 'show']);
});

// Patient prescriptions routes (requires authentication)
Route::middleware('auth:sanctum')->prefix('prescriptions')->group(function () {
    Route::get('/', [PrescriptionController::class, 'index']);
    Route::get('/{prescription}', [PrescriptionController::class, 'show']);
});

// Patient notifications routes (requires authentication)
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/{notification}', [NotificationController::class, 'destroy']);
});

// Admin routes (requires authentication + admin role)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/today', [DashboardController::class, 'today']);
    Route::get('/dashboard/weekly', [DashboardController::class, 'weekly']);
    Route::get('/dashboard/monthly', [DashboardController::class, 'monthly']);
    Route::get('/dashboard/chart', [DashboardController::class, 'chart']);
    Route::get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity']);
    Route::get('/dashboard/upcoming-appointments', [DashboardController::class, 'upcomingAppointments']);

    // Reports
    Route::get('/reports/appointments', [ReportController::class, 'appointments']);
    Route::get('/reports/revenue', [ReportController::class, 'revenue']);
    Route::get('/reports/patients', [ReportController::class, 'patients']);
    Route::get('/reports/appointments/export', [ReportController::class, 'exportAppointments']);
    Route::get('/reports/revenue/export', [ReportController::class, 'exportRevenue']);
    Route::get('/reports/patients/export', [ReportController::class, 'exportPatients']);

    // Clinic Settings
    Route::get('/settings', [ClinicSettingController::class, 'show']);
    Route::put('/settings', [ClinicSettingController::class, 'update']);
    Route::post('/settings/logo', [ClinicSettingController::class, 'uploadLogo']);
    Route::delete('/settings/logo', [ClinicSettingController::class, 'deleteLogo']);

    // Schedules
    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::post('/schedules', [ScheduleController::class, 'store']);
    Route::get('/schedules/{schedule}', [ScheduleController::class, 'show']);
    Route::put('/schedules/{schedule}', [ScheduleController::class, 'update']);
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy']);
    Route::put('/schedules/{schedule}/toggle', [ScheduleController::class, 'toggle']);

    // Vacations
    Route::get('/vacations', [VacationController::class, 'index']);
    Route::post('/vacations', [VacationController::class, 'store']);
    Route::get('/vacations/{vacation}', [VacationController::class, 'show']);
    Route::put('/vacations/{vacation}', [VacationController::class, 'update']);
    Route::delete('/vacations/{vacation}', [VacationController::class, 'destroy']);

    // Appointments
    Route::get('/appointments', [AdminAppointmentController::class, 'index']);
    Route::get('/appointments/today', [AdminAppointmentController::class, 'today']);
    Route::get('/appointments/upcoming', [AdminAppointmentController::class, 'upcoming']);
    Route::get('/appointments/for-date', [AdminAppointmentController::class, 'forDate']);
    Route::get('/appointments/statistics', [AdminAppointmentController::class, 'statistics']);
    Route::get('/appointments/{appointment}', [AdminAppointmentController::class, 'show']);
    Route::post('/appointments/{appointment}/confirm', [AdminAppointmentController::class, 'confirm']);
    Route::post('/appointments/{appointment}/complete', [AdminAppointmentController::class, 'complete']);
    Route::post('/appointments/{appointment}/cancel', [AdminAppointmentController::class, 'cancel']);
    Route::post('/appointments/{appointment}/no-show', [AdminAppointmentController::class, 'noShow']);
    Route::put('/appointments/{appointment}/notes', [AdminAppointmentController::class, 'updateNotes']);

    // Patients
    Route::get('/patients', [AdminPatientController::class, 'index']);
    Route::get('/patients/summary', [AdminPatientController::class, 'summary']);
    Route::get('/patients/{patient}', [AdminPatientController::class, 'show']);
    Route::get('/patients/{patient}/appointments', [AdminPatientController::class, 'appointments']);
    Route::get('/patients/{patient}/statistics', [AdminPatientController::class, 'statistics']);
    Route::put('/patients/{patient}/profile', [AdminPatientController::class, 'updateProfile']);
    Route::put('/patients/{patient}/status', [AdminPatientController::class, 'toggleStatus']);
    Route::post('/patients/{patient}/notes', [AdminPatientController::class, 'addNotes']);

    // Medical Records
    Route::get('/medical-records', [AdminMedicalRecordController::class, 'index']);
    Route::get('/medical-records/follow-ups-due', [AdminMedicalRecordController::class, 'followUpsDue']);
    Route::post('/medical-records', [AdminMedicalRecordController::class, 'store']);
    Route::get('/medical-records/{medicalRecord}', [AdminMedicalRecordController::class, 'show']);
    Route::put('/medical-records/{medicalRecord}', [AdminMedicalRecordController::class, 'update']);
    Route::delete('/medical-records/{medicalRecord}', [AdminMedicalRecordController::class, 'destroy']);
    Route::get('/patients/{patient}/medical-records', [AdminMedicalRecordController::class, 'byPatient']);

    // Prescriptions
    Route::get('/prescriptions', [AdminPrescriptionController::class, 'index']);
    Route::post('/prescriptions', [AdminPrescriptionController::class, 'store']);
    Route::get('/prescriptions/{prescription}', [AdminPrescriptionController::class, 'show']);
    Route::put('/prescriptions/{prescription}', [AdminPrescriptionController::class, 'update']);
    Route::delete('/prescriptions/{prescription}', [AdminPrescriptionController::class, 'destroy']);
    Route::post('/prescriptions/{prescription}/dispense', [AdminPrescriptionController::class, 'markAsDispensed']);
    Route::get('/prescriptions/{prescription}/pdf', [AdminPrescriptionController::class, 'streamPdf']);
    Route::get('/prescriptions/{prescription}/download', [AdminPrescriptionController::class, 'downloadPdf']);
    Route::get('/patients/{patient}/prescriptions', [AdminPrescriptionController::class, 'byPatient']);

    // Attachments (nested under medical records)
    Route::get('/medical-records/{medicalRecord}/attachments', [AttachmentController::class, 'index']);
    Route::post('/medical-records/{medicalRecord}/attachments', [AttachmentController::class, 'store']);
    Route::get('/medical-records/{medicalRecord}/attachments/{attachment}', [AttachmentController::class, 'show']);
    Route::delete('/medical-records/{medicalRecord}/attachments/{attachment}', [AttachmentController::class, 'destroy']);
    Route::get('/medical-records/{medicalRecord}/attachments/{attachment}/download', [AttachmentController::class, 'download']);

    // Payments
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/statistics', [PaymentController::class, 'statistics']);
    Route::get('/payments/report', [PaymentController::class, 'report']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    Route::put('/payments/{payment}', [PaymentController::class, 'update']);
    Route::post('/payments/{payment}/mark-paid', [PaymentController::class, 'markAsPaid']);
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund']);
    Route::get('/appointments/{appointment}/payment', [PaymentController::class, 'byAppointment']);
});
