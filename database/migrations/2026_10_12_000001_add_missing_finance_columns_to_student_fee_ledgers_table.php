<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_fee_ledgers')) {
            return;
        }

        Schema::table('student_fee_ledgers', function (Blueprint $table) {
            if (!Schema::hasColumn('student_fee_ledgers', 'parent_amount')) {
                $table->decimal('parent_amount', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('student_fee_ledgers', 'parent_balance')) {
                $table->decimal('parent_balance', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('student_fee_ledgers', 'govt_capitation_amount')) {
                $table->decimal('govt_capitation_amount', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('student_fee_ledgers', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_fee_ledgers')) {
            return;
        }

        Schema::table('student_fee_ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('student_fee_ledgers', 'notes')) {
                $table->dropColumn('notes');
            }

            if (Schema::hasColumn('student_fee_ledgers', 'govt_capitation_amount')) {
                $table->dropColumn('govt_capitation_amount');
            }

            if (Schema::hasColumn('student_fee_ledgers', 'parent_balance')) {
                $table->dropColumn('parent_balance');
            }

            if (Schema::hasColumn('student_fee_ledgers', 'parent_amount')) {
                $table->dropColumn('parent_amount');
            }
        });
    }
};