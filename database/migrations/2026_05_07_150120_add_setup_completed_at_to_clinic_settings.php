<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_settings', function (Blueprint $table) {
            // Set when the doctor completes the first-run wizard. Null means
            // the install hasn't been onboarded yet — admin pages redirect
            // to /admin/setup until this is filled.
            $table->timestamp('setup_completed_at')->nullable()->after('about_text');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_settings', function (Blueprint $table) {
            $table->dropColumn('setup_completed_at');
        });
    }
};
