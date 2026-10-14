<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                // Keep simple string; app-level validation controls allowed roles
                $table->string('role', 50)->default('teacher')->after('password');
                $table->index('role', 'users_role_idx');
            });
        } else {
            // Ensure existing null/empty roles get sane default value
            DB::table('users')
                ->whereNull('role')
                ->orWhere('role', '')
                ->update(['role' => 'teacher']);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropIndex('users_role_idx');
                } catch (\Throwable $e) {
                    // ignore if index missing
                }

                $table->dropColumn('role');
            });
        }
    }
};