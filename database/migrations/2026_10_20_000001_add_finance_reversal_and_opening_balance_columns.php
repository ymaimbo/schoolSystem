<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Opening balance on accounts
        if (Schema::hasTable('student_fee_accounts') && !Schema::hasColumn('student_fee_accounts', 'opening_balance')) {
            Schema::table('student_fee_accounts', function (Blueprint $table) {
                $table->decimal('opening_balance', 12, 2)->default(0)->after('total_fee_due');
            });
        }

        // 2. Payment reversal fields
        if (Schema::hasTable('student_fee_payments')) {
            Schema::table('student_fee_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('student_fee_payments', 'status')) {
                    $table->string('status', 20)->default('active')->after('notes');
                }
                if (!Schema::hasColumn('student_fee_payments', 'reversed_at')) {
                    $table->timestamp('reversed_at')->nullable()->after('status');
                }
                if (!Schema::hasColumn('student_fee_payments', 'reversed_by')) {
                    $table->unsignedBigInteger('reversed_by')->nullable()->after('reversed_at');
                    $table->foreign('reversed_by')
                          ->references('id')->on('users')
                          ->nullOnDelete();
                }
                if (!Schema::hasColumn('student_fee_payments', 'reversal_reason')) {
                    $table->string('reversal_reason', 255)->nullable()->after('reversed_by');
                }
            });
        }

        // 3. Allocation status
        if (Schema::hasTable('student_fee_allocations') && !Schema::hasColumn('student_fee_allocations', 'status')) {
            Schema::table('student_fee_allocations', function (Blueprint $table) {
                $table->string('status', 20)->default('active')->after('allocation_order');
                $table->index(['student_fee_payment_id', 'status'], 'sfa_payment_status_idx');
            });
        }
    }

    public function down(): void
    {
        // Forward-only: intentionally left empty.
        // Rolling back reversal columns would destroy audit history.
    }
};