<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_fee_accounts')) {
            return;
        }

        if (!Schema::hasColumn('student_fee_accounts', 'fee_structure_id')) {
            Schema::table('student_fee_accounts', function (Blueprint $table) {
                $table->unsignedBigInteger('fee_structure_id')->nullable()->after('student_id');
                $table->index('fee_structure_id', 'student_fee_accounts_fee_structure_id_idx');
            });
        }

        if (Schema::hasTable('fee_structures')) {
            Schema::table('student_fee_accounts', function (Blueprint $table) {
                try {
                    $table->foreign('fee_structure_id', 'student_fee_accounts_fee_structure_fk')
                        ->references('id')
                        ->on('fee_structures')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // ignore if already exists
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_fee_accounts')) {
            return;
        }

        if (Schema::hasColumn('student_fee_accounts', 'fee_structure_id')) {
            Schema::table('student_fee_accounts', function (Blueprint $table) {
                try {
                    $table->dropForeign('student_fee_accounts_fee_structure_fk');
                } catch (\Throwable $e) {
                }

                try {
                    $table->dropIndex('student_fee_accounts_fee_structure_id_idx');
                } catch (\Throwable $e) {
                }

                $table->dropColumn('fee_structure_id');
            });
        }
    }
};