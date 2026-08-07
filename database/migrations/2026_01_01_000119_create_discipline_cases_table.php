<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discipline_cases', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 16); // student, worker
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('worker_name')->nullable();
            $table->string('worker_department')->nullable();
            $table->string('case_title');
            $table->text('description');
            $table->string('status', 16)->default('pending'); // pending, ongoing, resolved
            $table->date('reported_on');
            $table->text('action_taken')->nullable();
            $table->string('next_step')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['subject_type', 'status']);
            $table->index(['reported_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discipline_cases');
    }
};