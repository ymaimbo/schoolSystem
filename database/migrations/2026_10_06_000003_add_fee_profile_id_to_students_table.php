<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'fee_profile_id')) {
                $table->foreignId('fee_profile_id')
                    ->nullable()
                    ->after('school_id')
                    ->constrained('fee_profiles')
                    ->nullOnDelete();

                $table->index(['school_id', 'fee_profile_id'], 'students_school_profile_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'fee_profile_id')) {
                $table->dropIndex('students_school_profile_idx');
                $table->dropConstrainedForeignId('fee_profile_id');
            }
        });
    }
};