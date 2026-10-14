<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fee_profile_votehead_priorities')) {
            return;
        }

        Schema::create('fee_profile_votehead_priorities', function (Blueprint $table) {
            $table->id();

            // School scope
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            // Profile + Votehead links
            $table->foreignId('fee_profile_id')->constrained('fee_profiles')->cascadeOnDelete();
            $table->foreignId('vote_head_id')->constrained('vote_heads')->cascadeOnDelete();

            // Priority order used by FeeAllocationService (profile fallback)
            $table->unsignedInteger('priority_order')->default(1);

            // Optional semantics flags
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // No duplicate votehead rows for same profile in same school
            $table->unique(
                ['school_id', 'fee_profile_id', 'vote_head_id'],
                'fpvp_school_profile_votehead_unique'
            );

            // Optional strict unique position per profile
            $table->unique(
                ['school_id', 'fee_profile_id', 'priority_order'],
                'fpvp_school_profile_priority_unique'
            );

            $table->index(
                ['school_id', 'fee_profile_id', 'is_active', 'priority_order'],
                'fpvp_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_profile_votehead_priorities');
    }
};