<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('admission_no')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender', 16);
            $table->unsignedTinyInteger('form_level');
            $table->string('stream', 32)->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();

            $table->index(['form_level', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};