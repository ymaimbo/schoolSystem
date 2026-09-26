<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subject_teacher_assignments')) {
            Schema::create('subject_teacher_assignments', function (Blueprint $table) {
                $table->id();

                // Teacher link (your system currently uses user_id in class assignments)
                $table->unsignedBigInteger('user_id')->index();

                // Scope definition
                $table->string('subject', 120);
                $table->string('class_level', 120);
                $table->string('stream', 120)->nullable(); // null/blank => all streams in class

                // Active flag
                $table->boolean('is_active')->default(true)->index();

                $table->timestamps();

                $table->unique(
                    ['user_id', 'subject', 'class_level', 'stream'],
                    'sta_user_subject_class_stream_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_teacher_assignments');
    }
};