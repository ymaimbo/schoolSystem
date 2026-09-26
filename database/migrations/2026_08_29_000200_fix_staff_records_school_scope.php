<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('staff_records', 'school_id')) {
            Schema::table('staff_records', function (Blueprint $table) {
                $table->foreignId('school_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        // backfill school_id from recorded_by user where possible
        DB::statement("
            UPDATE staff_records sr
            JOIN users u ON u.id = sr.recorded_by
            SET sr.school_id = u.school_id
            WHERE sr.school_id IS NULL
        ");

        // if still null rows remain, assign first school as fallback (optional safe-guard)
        DB::statement("
            UPDATE staff_records
            SET school_id = (SELECT id FROM schools ORDER BY id ASC LIMIT 1)
            WHERE school_id IS NULL
        ");

        Schema::table('staff_records', function (Blueprint $table) {
            $table->index('school_id');
        });
    }

    public function down(): void
    {
        // no destructive rollback for data backfill
    }
};