<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'education_system')) {
                $table->string('education_system', 20)->default('8-4-4')->after('gender');
            }

            if (! Schema::hasColumn('students', 'class_level')) {
                $table->string('class_level', 20)->nullable()->after('education_system');
            }

            if (! Schema::hasColumn('students', 'pathway')) {
                $table->string('pathway', 100)->nullable()->after('class_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach (['education_system', 'class_level', 'pathway'] as $col) {
                if (Schema::hasColumn('students', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};