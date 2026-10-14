<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_fee_ledgers')) {
            Schema::create('student_fee_ledgers', function (Blueprint $table) {
                $table->id();

                // Keep as plain columns first; add FKs later when referenced tables exist
                $table->unsignedBigInteger('school_id')->nullable();
                $table->unsignedBigInteger('student_id')->nullable();
                $table->unsignedBigInteger('student_fee_account_id')->nullable();
                $table->unsignedBigInteger('fee_structure_line_id')->nullable();
                $table->unsignedBigInteger('vote_head_id')->nullable();

                // Existing/new schema compatibility
                $table->enum('source', ['parent', 'capitation'])->default('parent');
                $table->enum('term', ['T1', 'T2', 'T3', 'ANNUAL'])->default('ANNUAL');

                $table->decimal('expected_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('balance_amount', 12, 2)->default(0);
                $table->enum('status', ['pending', 'partial', 'cleared'])->default('pending');

                // Backward compatibility fields used by your service/model
                $table->decimal('term1_amount', 12, 2)->default(0);
                $table->decimal('term2_amount', 12, 2)->default(0);
                $table->decimal('term3_amount', 12, 2)->default(0);
                $table->decimal('parent_amount', 12, 2)->default(0);
                $table->decimal('parent_balance', 12, 2)->default(0);
                $table->decimal('govt_capitation_amount', 12, 2)->default(0);
                $table->text('notes')->nullable();

                $table->timestamps();
            });
        }

        // Ensure compatibility columns if table pre-existed in partial form
        $this->ensureColumn('student_fee_ledgers', 'school_id', 'BIGINT UNSIGNED NULL');
        $this->ensureColumn('student_fee_ledgers', 'term1_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('student_fee_ledgers', 'term2_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('student_fee_ledgers', 'term3_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('student_fee_ledgers', 'parent_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('student_fee_ledgers', 'parent_balance', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('student_fee_ledgers', 'govt_capitation_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('student_fee_ledgers', 'notes', 'TEXT NULL');

        // Indexes (only when columns exist)
        $this->ensureIndexIfColumnsExist('student_fee_ledgers', 'sfl_school_idx', ['school_id']);
        $this->ensureIndexIfColumnsExist('student_fee_ledgers', 'sfl_account_votehead_idx', ['student_fee_account_id', 'vote_head_id']);

        // FKs only if referenced tables exist
        $this->addFkIfPossible('student_fee_ledgers', 'student_fee_ledgers_school_id_foreign', 'school_id', 'schools', 'id', 'cascade');
        $this->addFkIfPossible('student_fee_ledgers', 'student_fee_ledgers_student_id_foreign', 'student_id', 'students', 'id', 'cascade');
        $this->addFkIfPossible('student_fee_ledgers', 'student_fee_ledgers_student_fee_account_id_foreign', 'student_fee_account_id', 'student_fee_accounts', 'id', 'cascade');
        $this->addFkIfPossible('student_fee_ledgers', 'student_fee_ledgers_fee_structure_line_id_foreign', 'fee_structure_line_id', 'fee_structure_lines', 'id', 'cascade');
        $this->addFkIfPossible('student_fee_ledgers', 'student_fee_ledgers_vote_head_id_foreign', 'vote_head_id', 'vote_heads', 'id', 'cascade');
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_fee_ledgers')) {
            return;
        }

        foreach ([
            'student_fee_ledgers_school_id_foreign',
            'student_fee_ledgers_student_id_foreign',
            'student_fee_ledgers_student_fee_account_id_foreign',
            'student_fee_ledgers_fee_structure_line_id_foreign',
            'student_fee_ledgers_vote_head_id_foreign',
        ] as $fk) {
            try {
                Schema::table('student_fee_ledgers', fn (Blueprint $table) => $table->dropForeign($fk));
            } catch (\Throwable $e) {
            }
        }

        foreach (['sfl_school_idx', 'sfl_account_votehead_idx'] as $idx) {
            try {
                Schema::table('student_fee_ledgers', fn (Blueprint $table) => $table->dropIndex($idx));
            } catch (\Throwable $e) {
            }
        }

        Schema::dropIfExists('student_fee_ledgers');
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        if (!Schema::hasTable($table) || Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` ADD `{$column}` {$definition}");
    }

    private function ensureIndexIfColumnsExist(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // index exists
        }
    }

    private function addFkIfPossible(
        string $table,
        string $fkName,
        string $column,
        string $refTable,
        string $refColumn = 'id',
        string $onDelete = 'cascade'
    ): void {
        if (!Schema::hasTable($table) || !Schema::hasTable($refTable) || !Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($fkName, $column, $refTable, $refColumn, $onDelete) {
                $fk = $blueprint->foreign($column, $fkName)->references($refColumn)->on($refTable);
                $onDelete === 'set null' ? $fk->nullOnDelete() : $fk->cascadeOnDelete();
            });
        } catch (\Throwable $e) {
            // FK exists / cannot add
        }
    }
};