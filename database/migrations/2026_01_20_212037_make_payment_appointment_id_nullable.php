<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Make appointment_id nullable for direct payments (payments without appointments)
            $table->foreignId('appointment_id')->nullable()->change();

            // Add patient_id for direct payments that don't have an appointment
            $table->foreignId('patient_id')->nullable()->after('appointment_id')->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
            $table->foreignId('appointment_id')->nullable(false)->change();
        });
    }
};
