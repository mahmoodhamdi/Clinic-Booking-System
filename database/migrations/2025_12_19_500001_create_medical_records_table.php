<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->text('diagnosis');
            $table->text('symptoms')->nullable();
            $table->text('examination_notes')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->json('vital_signs')->nullable();
            $table->timestamps();

            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
