<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_items')) {
            return;
        }

        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'quantity')) {
                $table->decimal('quantity', 12, 2)->default(0)->after('category');
            }

            if (!Schema::hasColumn('inventory_items', 'unit')) {
                $table->string('unit', 20)->nullable()->after('quantity');
            }

            if (!Schema::hasColumn('inventory_items', 'reorder_level')) {
                $table->decimal('reorder_level', 12, 2)->nullable()->after('unit');
            }

            if (!Schema::hasColumn('inventory_items', 'notes')) {
                $table->text('notes')->nullable()->after('reorder_level');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('inventory_items')) {
            return;
        }

        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'notes')) {
                $table->dropColumn('notes');
            }

            if (Schema::hasColumn('inventory_items', 'reorder_level')) {
                $table->dropColumn('reorder_level');
            }

            if (Schema::hasColumn('inventory_items', 'unit')) {
                $table->dropColumn('unit');
            }

            if (Schema::hasColumn('inventory_items', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};