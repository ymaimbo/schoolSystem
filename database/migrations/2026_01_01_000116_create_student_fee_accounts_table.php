<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_student_fee_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fee_accounts', function (Blueprint $table) {
            $table->id();

            // Added for school scoping
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            // Keep one account per student
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();

            // Optional link to fee structure
            $table->foreignId('fee_structure_id')->nullable()->constrained('fee_structures')->nullOnDelete();

            // Positive = outstanding balance, negative = overpayment credit
            $table->decimal('total_fee_due', 12, 2)->default(0);

            $table->string('sponsor_org_name')->nullable();
            $table->string('sponsor_org_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id']);
            $table->index(['sponsor_org_name', 'sponsor_org_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_accounts');
    }
};