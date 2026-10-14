<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exams')) {
            return;
        }

        if (Schema::hasColumn('exams', 'subject')) {
            return;
        }

        Schema::table('exams', function (Blueprint $table) {
            // Add column in a position that actually exists
            if (Schema::hasColumn('exams', 'class_level')) {
                $table->string('subject', 120)->nullable()->after('class_level');
            } elseif (Schema::hasColumn('exams', 'class_id')) {
                $table->string('subject', 120)->nullable()->after('class_id');
            } elseif (Schema::hasColumn('exams', 'name')) {
                $table->string('subject', 120)->nullable()->after('name');
            } else {
                // safest fallback
                $table->string('subject', 120)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('exams')) {
            return;
        }

        if (Schema::hasColumn('exams', 'subject')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropColumn('subject');
            });
        }
    }
};