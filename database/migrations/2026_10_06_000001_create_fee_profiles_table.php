<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            // e.g DAY_SCHOLAR, BOARDER
            $table->string('code', 50);
            $table->string('name', 120);
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'code'], 'fee_profiles_school_code_unique');
            $table->index(['school_id', 'is_active'], 'fee_profiles_school_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_profiles');
    }
};