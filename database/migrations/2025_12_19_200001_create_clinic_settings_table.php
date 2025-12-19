<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_settings', function (Blueprint $table) {
            $table->id();
            $table->string('clinic_name');
            $table->string('doctor_name');
            $table->string('specialization')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('logo')->nullable();
            $table->unsignedInteger('slot_duration')->default(30); // minutes
            $table->unsignedInteger('max_patients_per_slot')->default(1);
            $table->unsignedInteger('advance_booking_days')->default(30);
            $table->unsignedInteger('cancellation_hours')->default(24);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_settings');
    }
};
