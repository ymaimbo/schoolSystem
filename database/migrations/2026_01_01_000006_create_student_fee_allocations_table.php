<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_fee_allocations')) {
            Schema::create('student_fee_allocations', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('school_id')->nullable();
                $table->unsignedBigInteger('student_fee_payment_id')->nullable();
                $table->unsignedBigInteger('student_fee_ledger_id')->nullable();
                $table->unsignedBigInteger('vote_head_id')->nullable();

                // Keep both for old/new compatibility
                $table->decimal('allocated_amount', 12, 2)->default(0);
                $table->decimal('amount', 12, 2)->default(0);

                $table->unsignedInteger('allocation_order')->default(1);
                $table->unsignedBigInteger('recorded_by')->nullable();

                $table->timestamps();
            });
        }

        // Ensure compatibility columns on partial/existing tables
        $this->ensureColumn('student_fee_allocations', 'school_id', 'BIGINT UNSIGNED NULL');
        $this->ensureColumn('student_fee_allocations', 'student_fee_payment_id', 'BIGINT UNSIGNED NULL');
        $this->ensureColumn('student_fee_allocations', 'student_fee_ledger_id', 'BIGINT UNSIGNED NULL');
        $this->ensureColumn('student_fee_allocations', 'vote_head_id', 'BIGINT UNSIGNED NULL');
        $this->ensureColumn('student_fee_allocations', 'allocated_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('student_fee_allocations', 'amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('student_fee_allocations', 'allocation_order', 'INT UNSIGNED NOT NULL DEFAULT 1');
        $this->ensureColumn('student_fee_allocations', 'recorded_by', 'BIGINT UNSIGNED NULL');

        $this->ensureIndexIfColumnsExist('student_fee_allocations', 'sfa_school_idx', ['school_id']);
        $this->ensureIndexIfColumnsExist('student_fee_allocations', 'sfa_payment_order_idx', ['student_fee_payment_id', 'allocation_order']);
        $this->ensureIndexIfColumnsExist('student_fee_allocations', 'sfa_ledger_idx', ['student_fee_ledger_id']);

        // Add foreign keys only when referenced tables exist
        $this->addFkIfPossible('student_fee_allocations', 'student_fee_allocations_school_id_foreign', 'school_id', 'schools', 'id', 'cascade');
        $this->addFkIfPossible('student_fee_allocations', 'student_fee_allocations_student_fee_payment_id_foreign', 'student_fee_payment_id', 'student_fee_payments', 'id', 'cascade');
        $this->addFkIfPossible('student_fee_allocations', 'student_fee_allocations_student_fee_ledger_id_foreign', 'student_fee_ledger_id', 'student_fee_ledgers', 'id', 'cascade');
        $this->addFkIfPossible('student_fee_allocations', 'student_fee_allocations_vote_head_id_foreign', 'vote_head_id', 'vote_heads', 'id', 'cascade');
        $this->addFkIfPossible('student_fee_allocations', 'student_fee_allocations_recorded_by_foreign', 'recorded_by', 'users', 'id', 'set null');
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_fee_allocations')) {
            return;
        }

        foreach ([
            'student_fee_allocations_school_id_foreign',
            'student_fee_allocations_student_fee_payment_id_foreign',
            'student_fee_allocations_student_fee_ledger_id_foreign',
            'student_fee_allocations_vote_head_id_foreign',
            'student_fee_allocations_recorded_by_foreign',
        ] as $fk) {
            try {
                Schema::table('student_fee_allocations', fn (Blueprint $table) => $table->dropForeign($fk));
            } catch (\Throwable $e) {
            }
        }

        foreach (['sfa_school_idx', 'sfa_payment_order_idx', 'sfa_ledger_idx'] as $idx) {
            try {
                Schema::table('student_fee_allocations', fn (Blueprint $table) => $table->dropIndex($idx));
            } catch (\Throwable $e) {
            }
        }

        Schema::dropIfExists('student_fee_allocations');
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
            // FK exists / cannot add now
        }
    }
};