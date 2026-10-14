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
            return;
        }

        // Ensure required columns exist (safe across environments)
        $this->ensureColumn('student_fee_allocations', 'allocated_amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('student_fee_allocations', 'amount', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
        $this->ensureColumn('student_fee_allocations', 'allocation_order', 'INT UNSIGNED NOT NULL DEFAULT 1');

        // Keep both columns synchronized for older rows
        DB::table('student_fee_allocations')
            ->whereNull('allocated_amount')
            ->update(['allocated_amount' => DB::raw('COALESCE(amount, 0)')]);

        DB::table('student_fee_allocations')
            ->whereNull('amount')
            ->update(['amount' => DB::raw('COALESCE(allocated_amount, 0)')]);

        // Add indexes only if not existing
        $this->ensureIndexIfColumnsExist(
            'student_fee_allocations',
            'sfa_payment_order_idx',
            ['student_fee_payment_id', 'allocation_order']
        );

        $this->ensureIndexIfColumnsExist(
            'student_fee_allocations',
            'sfa_ledger_idx',
            ['student_fee_ledger_id']
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_fee_allocations')) {
            return;
        }

        // Drop indexes only if they exist
        $this->dropIndexIfExists('student_fee_allocations', 'sfa_payment_order_idx');
        $this->dropIndexIfExists('student_fee_allocations', 'sfa_ledger_idx');

        // Usually do not drop columns in normalization rollback to avoid data loss.
        // Intentionally left as-is.
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

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
            $tableBlueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();

        $row = DB::selectOne(
            "SELECT COUNT(1) AS c
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?",
            [$db, $table, $indexName]
        );

        return (int) ($row->c ?? 0) > 0;
    }
};