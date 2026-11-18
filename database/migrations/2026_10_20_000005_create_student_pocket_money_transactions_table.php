<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_pocket_money_transactions')) {
            return;
        }

        Schema::create('student_pocket_money_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('student_pocket_money_account_id');
            $table->string('transaction_no', 255)->nullable()->unique();
            $table->enum('type', ['deposit', 'withdrawal']);
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 32)->nullable();
            $table->string('reference_no', 255)->nullable();
            $table->string('depositor_name', 255)->nullable();
            $table->string('depositor_phone', 32)->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->date('transaction_date');
            $table->string('status', 20)->default('active');
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->string('reversal_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for bursar dashboard and reporting
            $table->index(['school_id', 'transaction_date'], 'spmt_school_date_idx');
            $table->index(['student_pocket_money_account_id', 'status'], 'spmt_account_status_idx');
            $table->index(['student_id', 'transaction_date'], 'spmt_student_date_idx');
            $table->index(['school_id', 'type', 'status'], 'spmt_school_type_status_idx');

            // Foreign keys
            $table->foreign('school_id')
                ->references('id')
                ->on('schools')
                ->nullOnDelete();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();

            $table->foreign('student_pocket_money_account_id', 'spmt_account_fk')
                ->references('id')
                ->on('student_pocket_money_accounts')
                ->cascadeOnDelete();

            $table->foreign('recorded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('reversed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Forward-only. Pocket money history should never be dropped by rollback.
    }
};