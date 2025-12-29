<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('password_reset_tokens', 'attempts')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('locked_until')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'locked_until']);
        });
    }
};
