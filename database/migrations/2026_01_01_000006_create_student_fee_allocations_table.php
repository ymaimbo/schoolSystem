<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // If table already exists from a partial migration run, don't recreate.
        if (Schema::hasTable('student_fee_allocations')) {
            // Ensure required index exists (safe add if missing)
            Schema::table('student_fee_allocations', function (Blueprint $table) {
                // Laravel doesn't provide hasIndex check directly across all versions,
                // so we use a try/catch approach for compatibility.
                try {
                    $table->index(['student_fee_payment_id', 'allocation_order'], 'sfa_pay_order_idx');
                } catch (\Throwable $e) {
                    // index likely already exists; ignore
                }
            });
            return;
        }

        Schema::create('student_fee_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_fee_payment_id')->constrained('student_fee_payments')->cascadeOnDelete();
            $table->foreignId('student_fee_ledger_id')->constrained('student_fee_ledgers')->cascadeOnDelete();
            $table->decimal('allocated_amount', 12, 2);
            $table->unsignedInteger('allocation_order')->default(0);
            $table->timestamps();

            // Short name to avoid MySQL 64-char identifier limit
            $table->index(['student_fee_payment_id', 'allocation_order'], 'sfa_pay_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_allocations');
    }
};