<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add performance indexes for common query patterns.
     */
    public function up(): void
    {
        // Appointments table indexes
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['user_id', 'appointment_date', 'status'], 'idx_appointments_patient_date_status');
            $table->index('cancelled_at', 'idx_appointments_cancelled_at');
        });

        // Medical Records table indexes
        Schema::table('medical_records', function (Blueprint $table) {
            $table->index('appointment_id', 'idx_medical_records_appointment_id');
            $table->index('created_at', 'idx_medical_records_created_at');
            $table->index(['patient_id', 'created_at'], 'idx_medical_records_patient_created');
        });

        // Prescriptions table indexes
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->index('valid_until', 'idx_prescriptions_valid_until');
            $table->index('is_dispensed', 'idx_prescriptions_is_dispensed');
            $table->index(['medical_record_id', 'is_dispensed'], 'idx_prescriptions_record_dispensed');
        });

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->index('appointment_id', 'idx_payments_appointment_id');
            $table->index(['status', 'paid_at'], 'idx_payments_status_paid');
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('is_active', 'idx_users_is_active');
            $table->index(['role', 'is_active'], 'idx_users_role_active');
        });

        // Schedules table indexes
        Schema::table('schedules', function (Blueprint $table) {
            $table->index('is_active', 'idx_schedules_is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_patient_date_status');
            $table->dropIndex('idx_appointments_cancelled_at');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropIndex('idx_medical_records_appointment_id');
            $table->dropIndex('idx_medical_records_created_at');
            $table->dropIndex('idx_medical_records_patient_created');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropIndex('idx_prescriptions_valid_until');
            $table->dropIndex('idx_prescriptions_is_dispensed');
            $table->dropIndex('idx_prescriptions_record_dispensed');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_appointment_id');
            $table->dropIndex('idx_payments_status_paid');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_is_active');
            $table->dropIndex('idx_users_role_active');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedules_is_active');
        });
    }
};
