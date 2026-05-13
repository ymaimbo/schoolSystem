<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('term', 16); // Term 1, Term 2, Term 3
            $table->unsignedSmallInteger('year');
            $table->date('exam_date');
            $table->decimal('max_score', 8, 2)->default(100);
            $table->string('status', 16)->default('draft'); // draft, published, closed
            $table->timestamps();

            $table->index(['year', 'term', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};