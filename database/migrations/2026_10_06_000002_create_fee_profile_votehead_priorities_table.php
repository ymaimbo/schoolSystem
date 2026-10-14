<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_profile_votehead_priorities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('fee_profile_id')->constrained('fee_profiles')->cascadeOnDelete();
            $table->foreignId('vote_head_id')->constrained('vote_heads')->restrictOnDelete();

            // allocation order; lower = higher priority
            $table->unsignedInteger('priority_order')->default(100);

            // policy flags
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['school_id', 'fee_profile_id', 'vote_head_id'],
                'fvp_school_profile_votehead_unique'
            );

            $table->unique(
                ['school_id', 'fee_profile_id', 'priority_order', 'vote_head_id'],
                'fvp_school_profile_priority_votehead_unique'
            );

            $table->index(
                ['school_id', 'fee_profile_id', 'is_active', 'priority_order'],
                'fvp_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_profile_votehead_priorities');
    }
};