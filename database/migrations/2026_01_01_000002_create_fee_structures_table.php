<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. 2026 Day Scholars
            $table->unsignedSmallInteger('year');
            $table->enum('category', ['day_scholar', 'boarder'])->default('day_scholar');
            $table->string('class_level')->nullable(); // optional
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['year', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};