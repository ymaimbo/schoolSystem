<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('schools')) {
            throw new RuntimeException("schools table does not exist. Run schools migration first.");
        }

        // 1) Ensure at least one school exists (for backfill)
        $defaultSchoolId = DB::table('schools')->value('id');

        if (!$defaultSchoolId) {
            $defaultSchoolId = DB::table('schools')->insertGetId([
                'name' => 'Default School',
                'slug' => 'default-school',
                'code' => 'DEFAULT001',
                'location' => 'Default Location',
                'county' => 'Default County',
                'type' => 'Public Mixed Boarding School',
                'status' => 'Auto-created during multi-school migration',
                'note' => null,
                'logo_path' => null,
                'hero_image_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tables = [
            'users',
            'students',
            'student_fee_accounts',
            'student_fee_payments',
            'student_fee_ledgers',
            'student_fee_allocations',
            'vote_heads',
            'fee_structures',
            'fee_structure_lines',
            'finance_transactions',
            'payment_vouchers',
            'inventory_items',
            'exam_results',
            'discipline_cases',
            'sports_department_records',
            'staff_records',
            'staff_members',
            'programs',
            'timetables',
            'parent_messages',
        ];

        // 2) Add school_id as nullable + FK
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            if (!Schema::hasColumn($table, 'school_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('school_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('schools')
                        ->nullOnDelete();
                });
            }
        }

        // 3) Backfill existing rows to default school
        foreach ($tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'school_id')) {
                continue;
            }

            DB::table($table)
                ->whereNull('school_id')
                ->update(['school_id' => $defaultSchoolId]);
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'students',
            'student_fee_accounts',
            'student_fee_payments',
            'student_fee_ledgers',
            'student_fee_allocations',
            'vote_heads',
            'fee_structures',
            'fee_structure_lines',
            'finance_transactions',
            'payment_vouchers',
            'inventory_items',
            'exam_results',
            'discipline_cases',
            'sports_department_records',
            'staff_records',
            'staff_members',
            'programs',
            'timetables',
            'parent_messages',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'school_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('school_id');
            });
        }
    }
};