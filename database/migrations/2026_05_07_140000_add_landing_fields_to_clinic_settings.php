<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_settings', function (Blueprint $table) {
            // Short marketing line shown on the landing hero.
            $table->string('tagline', 255)->nullable()->after('specialization');

            // Path to a hero/banner image on the public disk.
            $table->string('hero_image')->nullable()->after('logo');

            // List of services rendered on the landing — JSON of {title, description}.
            // Optional; if null, the landing falls back to a translated default list.
            $table->json('services')->nullable()->after('hero_image');

            // Long-form "about the doctor / clinic" copy. Plain text; sanitized
            // before render to allow safe newlines but no HTML.
            $table->text('about_text')->nullable()->after('services');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_settings', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'hero_image', 'services', 'about_text']);
        });
    }
};
