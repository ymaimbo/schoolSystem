<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_pocket_money_accounts')) {
            return;
        }

        Schema::create('student_pocket_money_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('student_id', 'spma_student_unique');
            $table->index('school_id', 'spma_school_idx');
            $table->index(['school_id', 'student_id'], 'spma_school_student_idx');

            $table->foreign('school_id')
                ->references('id')
                ->on('schools')
                ->nullOnDelete();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Forward-only. Pocket money history should never be dropped by rollback.
    }
};