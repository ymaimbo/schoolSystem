<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'subject')) {
                $table->string('subject', 120)->nullable()->after('class_level');
            }
        });

        DB::table('exams')
            ->whereNull('subject')
            ->orWhere('subject', '')
            ->update(['subject' => 'General']);
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'subject')) {
                $table->dropColumn('subject');
            }
        });
    }
};