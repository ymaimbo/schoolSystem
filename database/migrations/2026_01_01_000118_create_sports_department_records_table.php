<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sports_department_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('sport_name', 100);
            $table->string('team_category', 16)->default('mixed'); // boys, girls, mixed
            $table->string('position', 100)->nullable();
            $table->string('status', 16)->default('active'); // active, inactive
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sport_name', 'team_category']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports_department_records');
    }
};