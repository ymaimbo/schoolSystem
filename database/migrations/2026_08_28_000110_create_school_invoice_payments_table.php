<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_invoice_id')->nullable()->constrained('school_invoices')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('method')->default('bank'); // bank | mpesa | cash | cheque
            $table->string('reference_no')->nullable();
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_invoice_payments');
    }
};