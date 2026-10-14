<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_students_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            // Admission number should be unique per school (not globally).
            $table->string('admission_no');
            $table->string('first_name');
            $table->string('last_name');

            $table->string('parent_name')->nullable();
            $table->string('parent_phone', 32)->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone', 32)->nullable();
            $table->string('guardian_relationship')->nullable();
            $table->string('contact_preference', 16)->default('parent');

            $table->string('gender', 16);
            $table->string('education_system', 16)->default('8-4-4'); // 8-4-4 | CBC
            $table->string('class_level', 20); // Form 1..4 | Grade 10..12
            $table->string('pathway', 100)->nullable(); // CBC pathway
            $table->decimal('entry_marks', 5, 2)->default(0);
            $table->unsignedTinyInteger('form_level')->default(1);
            $table->string('stream', 32)->nullable();
            $table->string('status', 32)->default('active'); // active | alumni | transferred

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['school_id', 'admission_no']);
            $table->index(['school_id', 'class_level', 'status']);
            $table->index(['school_id', 'education_system']);
            $table->index(['school_id', 'last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};