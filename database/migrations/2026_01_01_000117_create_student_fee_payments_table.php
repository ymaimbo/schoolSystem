<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_student_fee_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fee_payments', function (Blueprint $table) {
            $table->id();

            // Added for school scoping
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_fee_account_id')->constrained('student_fee_accounts')->cascadeOnDelete();

            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 32); // cash | bank | mpesa | cheque | transfer
            $table->string('organization_name')->nullable();
            $table->string('organization_id')->nullable();
            $table->string('receipt_no')->nullable()->unique();
            $table->date('paid_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'paid_at']);
            $table->index(['payment_method', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_payments');
    }
};