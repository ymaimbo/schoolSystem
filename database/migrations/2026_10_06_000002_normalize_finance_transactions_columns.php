<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finance_transactions')) {
            return;
        }

        Schema::table('finance_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_transactions', 'status')) {
                $table->string('status', 30)->nullable()->after('amount');
            }

            if (!Schema::hasColumn('finance_transactions', 'entry_date')) {
                $table->date('entry_date')->nullable()->after('status');
            }

            if (!Schema::hasColumn('finance_transactions', 'source_module')) {
                $table->string('source_module', 80)->nullable()->after('description');
            }

            if (!Schema::hasColumn('finance_transactions', 'reference_no')) {
                $table->string('reference_no', 120)->nullable()->after('source_module');
            }

            if (!Schema::hasColumn('finance_transactions', 'meta')) {
                $table->json('meta')->nullable()->after('reference_no');
            }
        });

        if (Schema::hasColumn('finance_transactions', 'status')) {
            DB::table('finance_transactions')
                ->whereNull('status')
                ->update(['status' => 'posted']);
        }

        if (Schema::hasColumn('finance_transactions', 'entry_date')) {
            DB::statement("
                UPDATE finance_transactions
                SET entry_date = COALESCE(entry_date, DATE(created_at), CURDATE())
            ");
        }

        if (Schema::hasColumn('finance_transactions', 'source_module')) {
            DB::table('finance_transactions')
                ->whereNull('source_module')
                ->update(['source_module' => 'legacy']);
        }

        try {
            Schema::table('finance_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('finance_transactions', 'school_id') && Schema::hasColumn('finance_transactions', 'entry_date')) {
                    $table->index(['school_id', 'entry_date'], 'ft_school_entry_date_idx');
                }
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('finance_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('finance_transactions', 'reference_no')) {
                    $table->index('reference_no', 'ft_reference_no_idx');
                }
            });
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('finance_transactions')) {
            return;
        }

        try {
            Schema::table('finance_transactions', function (Blueprint $table) {
                $table->dropIndex('ft_school_entry_date_idx');
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('finance_transactions', function (Blueprint $table) {
                $table->dropIndex('ft_reference_no_idx');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('finance_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('finance_transactions', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('finance_transactions', 'source_module')) {
                $table->dropColumn('source_module');
            }
            if (Schema::hasColumn('finance_transactions', 'entry_date')) {
                $table->dropColumn('entry_date');
            }
            if (Schema::hasColumn('finance_transactions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};