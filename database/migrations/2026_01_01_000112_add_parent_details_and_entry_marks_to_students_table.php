<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'parent_name')) {
                $table->string('parent_name', 255)->nullable()->after('last_name');
            }

            if (! Schema::hasColumn('students', 'parent_phone')) {
                $table->string('parent_phone', 32)->nullable()->after('parent_name');
            }

            if (! Schema::hasColumn('students', 'entry_marks')) {
                $table->decimal('entry_marks', 8, 2)->nullable()->after('pathway');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'entry_marks')) {
                $table->dropColumn('entry_marks');
            }
            if (Schema::hasColumn('students', 'parent_phone')) {
                $table->dropColumn('parent_phone');
            }
            if (Schema::hasColumn('students', 'parent_name')) {
                $table->dropColumn('parent_name');
            }
        });
    }
};