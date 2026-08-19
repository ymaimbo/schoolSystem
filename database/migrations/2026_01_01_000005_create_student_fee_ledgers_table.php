<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_fee_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_fee_account_id')->constrained('student_fee_accounts')->cascadeOnDelete();
            $table->foreignId('fee_structure_line_id')->constrained('fee_structure_lines')->cascadeOnDelete();
            $table->foreignId('vote_head_id')->constrained('vote_heads')->restrictOnDelete();

            $table->enum('source', ['parent', 'capitation']);
            $table->enum('term', ['T1', 'T2', 'T3', 'ANNUAL'])->default('ANNUAL');

            $table->decimal('expected_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);

            $table->enum('status', ['pending', 'partial', 'cleared'])->default('pending');
            $table->timestamps();

            $table->index(['student_fee_account_id', 'source', 'term']);
            $table->unique(
                ['student_fee_account_id', 'fee_structure_line_id', 'source', 'term'],
                'uniq_account_line_source_term'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_ledgers');
    }
};