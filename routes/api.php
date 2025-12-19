<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SlotController;
use App\Http\Controllers\Api\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Api\Admin\ClinicSettingController;
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

// Admin routes (requires authentication + admin role)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
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
});
