<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fee_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->decimal('total_fee_due', 12, 2)->default(0);
            $table->string('sponsor_org_name')->nullable();
            $table->string('sponsor_org_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sponsor_org_name', 'sponsor_org_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_accounts');
    }
};