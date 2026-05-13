<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_items')) {
            return;
        }

        // 1) Ensure target columns exist
        Schema::table('inventory_items', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_items', 'quantity')) {
                $table->decimal('quantity', 12, 2)->nullable()->after('category');
            }

            if (! Schema::hasColumn('inventory_items', 'reorder_level')) {
                $table->decimal('reorder_level', 12, 2)->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('inventory_items', 'notes')) {
                $table->text('notes')->nullable()->after('reorder_level');
            }
        });

        // 2) Copy values from legacy columns if they exist
        if (Schema::hasColumn('inventory_items', 'stock_quantity')) {
            DB::statement("
                UPDATE inventory_items
                SET quantity = COALESCE(quantity, stock_quantity)
                WHERE stock_quantity IS NOT NULL
            ");
        }

        if (Schema::hasColumn('inventory_items', 'minimum_stock')) {
            DB::statement("
                UPDATE inventory_items
                SET reorder_level = COALESCE(reorder_level, minimum_stock)
                WHERE minimum_stock IS NOT NULL
            ");
        }

        if (Schema::hasColumn('inventory_items', 'description')) {
            DB::statement("
                UPDATE inventory_items
                SET notes = COALESCE(notes, description)
                WHERE description IS NOT NULL AND description <> ''
            ");
        }

        // 3) Set defaults for null numeric values to avoid validation issues
        DB::statement("UPDATE inventory_items SET quantity = 0 WHERE quantity IS NULL");
        DB::statement("UPDATE inventory_items SET reorder_level = 0 WHERE reorder_level IS NULL");

        // 4) Drop legacy columns
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'stock_quantity')) {
                $table->dropColumn('stock_quantity');
            }

            if (Schema::hasColumn('inventory_items', 'minimum_stock')) {
                $table->dropColumn('minimum_stock');
            }

            if (Schema::hasColumn('inventory_items', 'description')) {
                $table->dropColumn('description');
            }
        });

        // 5) Enforce NOT NULL on quantity after data is cleaned
        // (Use raw SQL for compatibility)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_items MODIFY quantity DECIMAL(12,2) NOT NULL DEFAULT 0");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventory_items')) {
            return;
        }

        // Recreate legacy columns
        Schema::table('inventory_items', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_items', 'stock_quantity')) {
                $table->decimal('stock_quantity', 12, 2)->nullable()->after('category');
            }

            if (! Schema::hasColumn('inventory_items', 'minimum_stock')) {
                $table->decimal('minimum_stock', 12, 2)->nullable()->after('stock_quantity');
            }

            if (! Schema::hasColumn('inventory_items', 'description')) {
                $table->text('description')->nullable()->after('minimum_stock');
            }
        });

        // Copy data back
        DB::statement("
            UPDATE inventory_items
            SET stock_quantity = COALESCE(stock_quantity, quantity),
                minimum_stock = COALESCE(minimum_stock, reorder_level),
                description = COALESCE(description, notes)
        ");
    }
};