<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vote_heads', function (Blueprint $table) {
            // Remove the problematic global unique index
            $table->dropUnique('vote_heads_code_unique');
            
            // Add a school-scoped unique index instead
            $table->unique(['school_id', 'code'], 'vote_heads_school_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('vote_heads', function (Blueprint $table) {
            $table->dropUnique('vote_heads_school_code_unique');
            $table->unique('code', 'vote_heads_code_unique');
        });
    }
};