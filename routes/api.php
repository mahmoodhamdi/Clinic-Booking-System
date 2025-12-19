<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SlotController;
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
});
