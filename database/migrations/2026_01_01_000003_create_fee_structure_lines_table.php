<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_structure_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->cascadeOnDelete();
            $table->foreignId('vote_head_id')->constrained('vote_heads')->restrictOnDelete();

            $table->decimal('govt_capitation_amount', 12, 2)->default(0);
            $table->decimal('parent_total_amount', 12, 2)->default(0);

            $table->decimal('term1_amount', 12, 2)->default(0);
            $table->decimal('term2_amount', 12, 2)->default(0);
            $table->decimal('term3_amount', 12, 2)->default(0);

            $table->decimal('total_amount', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structure_lines');
    }
};