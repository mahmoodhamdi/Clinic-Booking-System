<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add soft deletes to critical tables for audit/compliance.
     * Medical and financial data should never be permanently deleted.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('appointments', 'deleted_at')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('medical_records', 'deleted_at')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('prescriptions', 'deleted_at')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('payments', 'deleted_at')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
