<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('class_level', 80);
            $table->string('stream', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // one teacher assignment per school
            $table->unique(['school_id', 'user_id'], 'cta_school_user_unique');

            $table->index(['school_id', 'class_level', 'stream'], 'cta_school_class_stream_index');
            $table->index(['school_id', 'is_active'], 'cta_school_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_teacher_assignments');
    }
};