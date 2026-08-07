<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_records', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('staff_type', 16); // teacher, worker
            $table->string('role_category', 32); // hod, class_teacher, teacher_on_duty, teacher, worker
            $table->string('department')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('employment_status', 16)->default('active'); // active, inactive
            $table->boolean('is_on_duty')->default(false);
            $table->date('duty_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['staff_type', 'role_category']);
            $table->index(['employment_status']);
            $table->index(['is_on_duty', 'duty_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_records');
    }
};