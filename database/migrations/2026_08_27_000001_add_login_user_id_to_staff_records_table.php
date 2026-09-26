<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_records')) {
            return;
        }

        if (! Schema::hasColumn('staff_records', 'login_user_id')) {
            Schema::table('staff_records', function (Blueprint $table): void {
                $table->unsignedBigInteger('login_user_id')->nullable()->after('recorded_by');
                $table->foreign('login_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('staff_records')) {
            return;
        }

        if (Schema::hasColumn('staff_records', 'login_user_id')) {
            Schema::table('staff_records', function (Blueprint $table): void {
                $table->dropForeign(['login_user_id']);
                $table->dropColumn('login_user_id');
            });
        }
    }
};