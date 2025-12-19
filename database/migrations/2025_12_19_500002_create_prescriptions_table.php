<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained()->onDelete('cascade');
            $table->string('prescription_number')->unique();
            $table->text('notes')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_dispensed')->default(false);
            $table->timestamp('dispensed_at')->nullable();
            $table->timestamps();

            $table->index('prescription_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
