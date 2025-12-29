<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        // For MySQL
        $indexes = $connection->select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     *
     * Add performance indexes for common query patterns.
     */
    public function up(): void
    {
        // Appointments table indexes
        if (!$this->indexExists('appointments', 'idx_appointments_patient_date_status')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index(['user_id', 'appointment_date', 'status'], 'idx_appointments_patient_date_status');
            });
        }
        if (!$this->indexExists('appointments', 'idx_appointments_cancelled_at')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index('cancelled_at', 'idx_appointments_cancelled_at');
            });
        }

        // Medical Records table indexes
        if (!$this->indexExists('medical_records', 'idx_medical_records_appointment_id')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->index('appointment_id', 'idx_medical_records_appointment_id');
            });
        }
        if (!$this->indexExists('medical_records', 'idx_medical_records_created_at')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->index('created_at', 'idx_medical_records_created_at');
            });
        }
        if (!$this->indexExists('medical_records', 'idx_medical_records_patient_created')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->index(['patient_id', 'created_at'], 'idx_medical_records_patient_created');
            });
        }

        // Prescriptions table indexes
        if (!$this->indexExists('prescriptions', 'idx_prescriptions_valid_until')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->index('valid_until', 'idx_prescriptions_valid_until');
            });
        }
        if (!$this->indexExists('prescriptions', 'idx_prescriptions_is_dispensed')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->index('is_dispensed', 'idx_prescriptions_is_dispensed');
            });
        }
        if (!$this->indexExists('prescriptions', 'idx_prescriptions_record_dispensed')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->index(['medical_record_id', 'is_dispensed'], 'idx_prescriptions_record_dispensed');
            });
        }

        // Payments table indexes
        if (!$this->indexExists('payments', 'idx_payments_appointment_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('appointment_id', 'idx_payments_appointment_id');
            });
        }
        if (!$this->indexExists('payments', 'idx_payments_status_paid')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['status', 'paid_at'], 'idx_payments_status_paid');
            });
        }

        // Users table indexes
        if (!$this->indexExists('users', 'idx_users_is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('is_active', 'idx_users_is_active');
            });
        }
        if (!$this->indexExists('users', 'idx_users_role_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['role', 'is_active'], 'idx_users_role_active');
            });
        }

        // Schedules table indexes
        if (!$this->indexExists('schedules', 'idx_schedules_is_active')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->index('is_active', 'idx_schedules_is_active');
            });
        }
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
