<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'guardian_name')) {
                $table->string('guardian_name')->nullable()->after('parent_phone');
            }

            if (!Schema::hasColumn('students', 'guardian_phone')) {
                $table->string('guardian_phone', 32)->nullable()->after('guardian_name');
            }

            if (!Schema::hasColumn('students', 'guardian_relationship')) {
                $table->string('guardian_relationship', 64)->nullable()->after('guardian_phone');
            }

            if (!Schema::hasColumn('students', 'contact_preference')) {
                $table->string('contact_preference', 16)->default('parent')->after('guardian_relationship');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'contact_preference')) {
                $table->dropColumn('contact_preference');
            }
            if (Schema::hasColumn('students', 'guardian_relationship')) {
                $table->dropColumn('guardian_relationship');
            }
            if (Schema::hasColumn('students', 'guardian_phone')) {
                $table->dropColumn('guardian_phone');
            }
            if (Schema::hasColumn('students', 'guardian_name')) {
                $table->dropColumn('guardian_name');
            }
        });
    }
};