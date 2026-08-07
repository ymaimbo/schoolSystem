<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('recipient_phone', 32);
            $table->string('message_type', 24); // notice | result
            $table->text('message_body');
            $table->string('status', 24)->default('sent'); // sent | failed | queued
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['message_type', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_messages');
    }
};