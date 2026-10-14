<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fee_profiles')) {
            return;
        }

        Schema::create('fee_profiles', function (Blueprint $table) {
            $table->id();

            // School scope (required by your controller/service patterns)
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['school_id', 'code'], 'fee_profiles_school_code_unique');
            $table->index(['school_id', 'is_active'], 'fee_profiles_school_active_idx');
            $table->index(['school_id', 'name'], 'fee_profiles_school_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_profiles');
    }
};