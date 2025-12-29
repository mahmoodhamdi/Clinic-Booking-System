<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\PatientProfile;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Vacation;
use App\Observers\AppointmentObserver;
use App\Observers\MedicalRecordObserver;
use App\Observers\PaymentObserver;
use App\Observers\ScheduleObserver;
use App\Observers\UserObserver;
use App\Observers\VacationObserver;
use App\Policies\AppointmentPolicy;
use App\Policies\MedicalRecordPolicy;
use App\Policies\PatientProfilePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PrescriptionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Appointment::observe(AppointmentObserver::class);
        Payment::observe(PaymentObserver::class);
        User::observe(UserObserver::class);
        Schedule::observe(ScheduleObserver::class);
        Vacation::observe(VacationObserver::class);
        MedicalRecord::observe(MedicalRecordObserver::class);

        // Register authorization policies
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(MedicalRecord::class, MedicalRecordPolicy::class);
        Gate::policy(Prescription::class, PrescriptionPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(PatientProfile::class, PatientProfilePolicy::class);

        // Configure rate limiting
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for API endpoints.
     */
    protected function configureRateLimiting(): void
    {
        // API rate limiter for authenticated users (60 requests/minute)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Stricter rate limiter for public endpoints (30 requests/minute)
        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Very strict rate limiter for auth endpoints (5 requests/minute)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Rate limiter for slots (20 requests/minute)
        RateLimiter::for('slots', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        // Rate limiter for booking (3 requests/minute to prevent spam)
        RateLimiter::for('booking', function (Request $request) {
            return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
        });
    }
}
