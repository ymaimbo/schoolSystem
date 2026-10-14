<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // fee_structure_lines normalization
        if (Schema::hasTable('fee_structure_lines')) {
            Schema::table('fee_structure_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('fee_structure_lines', 'vote_head_id')) {
                    $table->unsignedBigInteger('vote_head_id')->nullable()->after('fee_structure_id');
                }
                if (!Schema::hasColumn('fee_structure_lines', 'amount')) {
                    $table->decimal('amount', 12, 2)->nullable()->after('vote_head_id');
                }
                if (!Schema::hasColumn('fee_structure_lines', 'sort_order')) {
                    $table->integer('sort_order')->default(999)->after('amount');
                }
            });

            // backfill vote_head_id from votehead_id if legacy exists
            if (Schema::hasColumn('fee_structure_lines', 'votehead_id')) {
                DB::statement('UPDATE fee_structure_lines SET vote_head_id = COALESCE(vote_head_id, votehead_id)');
            }

            // backfill amount from known legacy fields if they exist
            $lineCols = Schema::getColumnListing('fee_structure_lines');
            foreach (['expected_amount', 'fee_amount', 'total', 'line_amount'] as $legacyAmountCol) {
                if (in_array($legacyAmountCol, $lineCols, true)) {
                    DB::statement("UPDATE fee_structure_lines SET amount = COALESCE(amount, {$legacyAmountCol})");
                }
            }

            // backfill sort_order from known legacy fields
            if (in_array('priority', Schema::getColumnListing('fee_structure_lines'), true)) {
                DB::statement('UPDATE fee_structure_lines SET sort_order = COALESCE(sort_order, priority, 999)');
            }
            if (in_array('allocation_order', Schema::getColumnListing('fee_structure_lines'), true)) {
                DB::statement('UPDATE fee_structure_lines SET sort_order = COALESCE(sort_order, allocation_order, 999)');
            }

            // final safety defaults
            DB::statement('UPDATE fee_structure_lines SET amount = COALESCE(amount, 0)');
            DB::statement('UPDATE fee_structure_lines SET sort_order = COALESCE(sort_order, 999)');
        }

        // student_fee_ledgers normalization
        if (Schema::hasTable('student_fee_ledgers')) {
            Schema::table('student_fee_ledgers', function (Blueprint $table) {
                if (!Schema::hasColumn('student_fee_ledgers', 'vote_head_id')) {
                    $table->unsignedBigInteger('vote_head_id')->nullable()->after('student_fee_account_id');
                }
                if (!Schema::hasColumn('student_fee_ledgers', 'expected_amount')) {
                    $table->decimal('expected_amount', 12, 2)->nullable()->after('vote_head_id');
                }
                if (!Schema::hasColumn('student_fee_ledgers', 'outstanding_amount')) {
                    $table->decimal('outstanding_amount', 12, 2)->nullable()->after('expected_amount');
                }
                if (!Schema::hasColumn('student_fee_ledgers', 'priority_order')) {
                    $table->integer('priority_order')->default(999)->after('outstanding_amount');
                }
            });

            // backfill vote_head_id from legacy votehead_id
            if (Schema::hasColumn('student_fee_ledgers', 'votehead_id')) {
                DB::statement('UPDATE student_fee_ledgers SET vote_head_id = COALESCE(vote_head_id, votehead_id)');
            }

            // backfill expected_amount from legacy amount/total_amount if present
            $ledgerCols = Schema::getColumnListing('student_fee_ledgers');
            foreach (['amount', 'total_amount'] as $legacyExpectedCol) {
                if (in_array($legacyExpectedCol, $ledgerCols, true)) {
                    DB::statement("UPDATE student_fee_ledgers SET expected_amount = COALESCE(expected_amount, {$legacyExpectedCol})");
                }
            }

            // backfill outstanding_amount from legacy columns
            foreach (['outstanding', 'amount_due', 'outstanding_balance', 'balance'] as $legacyOutCol) {
                if (in_array($legacyOutCol, Schema::getColumnListing('student_fee_ledgers'), true)) {
                    DB::statement("UPDATE student_fee_ledgers SET outstanding_amount = COALESCE(outstanding_amount, {$legacyOutCol})");
                }
            }

            // final safety defaults
            DB::statement('UPDATE student_fee_ledgers SET expected_amount = COALESCE(expected_amount, 0)');
            DB::statement('UPDATE student_fee_ledgers SET outstanding_amount = COALESCE(outstanding_amount, expected_amount, 0)');
            DB::statement('UPDATE student_fee_ledgers SET priority_order = COALESCE(priority_order, 999)');
        }
    }

    public function down(): void
    {
        // Keep rollback conservative: only remove columns we introduced if they exist.
        if (Schema::hasTable('fee_structure_lines')) {
            Schema::table('fee_structure_lines', function (Blueprint $table) {
                if (Schema::hasColumn('fee_structure_lines', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
                if (Schema::hasColumn('fee_structure_lines', 'amount')) {
                    $table->dropColumn('amount');
                }
                if (Schema::hasColumn('fee_structure_lines', 'vote_head_id')) {
                    $table->dropColumn('vote_head_id');
                }
            });
        }

        if (Schema::hasTable('student_fee_ledgers')) {
            Schema::table('student_fee_ledgers', function (Blueprint $table) {
                if (Schema::hasColumn('student_fee_ledgers', 'priority_order')) {
                    $table->dropColumn('priority_order');
                }
                if (Schema::hasColumn('student_fee_ledgers', 'outstanding_amount')) {
                    $table->dropColumn('outstanding_amount');
                }
                if (Schema::hasColumn('student_fee_ledgers', 'expected_amount')) {
                    $table->dropColumn('expected_amount');
                }
                if (Schema::hasColumn('student_fee_ledgers', 'vote_head_id')) {
                    $table->dropColumn('vote_head_id');
                }
            });
        }
    }
};