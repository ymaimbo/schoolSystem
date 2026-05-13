<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('type', 16); // income | expense
            $table->string('category', 64);
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 32)->nullable();
            $table->string('reference_no', 64)->nullable();
            $table->timestamps();

            $table->index(['entry_date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};