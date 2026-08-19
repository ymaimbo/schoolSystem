<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_fee_accounts', function (Blueprint $table) {
            $table->foreignId('fee_structure_id')
                ->nullable()
                ->after('student_id')
                ->constrained('fee_structures')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_fee_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_structure_id');
        });
    }
};