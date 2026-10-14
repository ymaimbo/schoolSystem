<?php
// database/migrations/2026_10_01_000001_add_billing_access_to_schools_and_create_access_events_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->decimal('billing_cycle_amount_due', 12, 2)->default(0)->after('slug');
            $table->decimal('billing_cycle_amount_paid', 12, 2)->default(0)->after('billing_cycle_amount_due');
            $table->decimal('billing_paid_percent', 5, 2)->default(0)->after('billing_cycle_amount_paid');

            // locked | quarter_only | half_only | full
            $table->string('billing_access_stage', 32)->default('locked')->after('billing_paid_percent');

            $table->timestamp('billing_grace_until')->nullable()->after('billing_access_stage');
            $table->boolean('billing_access_override')->default(false)->after('billing_grace_until');
            $table->text('billing_access_override_reason')->nullable()->after('billing_access_override');
            $table->timestamp('billing_restricted_at')->nullable()->after('billing_access_override_reason');

            $table->index('billing_access_stage');
            $table->index('billing_access_override');
        });

        Schema::create('school_access_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('old_stage', 32)->nullable();
            $table->string('new_stage', 32);

            $table->decimal('amount_due', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('paid_percent', 5, 2)->default(0);

            $table->boolean('override_enabled')->default(false);
            $table->text('override_reason')->nullable();
            $table->string('reason', 255)->nullable(); // manual_update | recalculate | auto_sync

            $table->timestamps();

            $table->index(['school_id', 'new_stage']);
            $table->index(['school_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_access_events');

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'billing_cycle_amount_due',
                'billing_cycle_amount_paid',
                'billing_paid_percent',
                'billing_access_stage',
                'billing_grace_until',
                'billing_access_override',
                'billing_access_override_reason',
                'billing_restricted_at',
            ]);
        });
    }
};