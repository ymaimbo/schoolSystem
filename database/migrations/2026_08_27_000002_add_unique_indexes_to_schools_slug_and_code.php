<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schools')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table): void {
            // Prevent duplicate slugs at DB level (race-condition safe).
            $table->unique('slug', 'schools_slug_unique_idx');

            // Prevent duplicate school codes at DB level.
            // MySQL allows multiple NULL values in unique indexes, which is fine here.
            $table->unique('code', 'schools_code_unique_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('schools')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table): void {
            $table->dropUnique('schools_slug_unique_idx');
            $table->dropUnique('schools_code_unique_idx');
        });
    }
};