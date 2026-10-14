<?php
// app/Http/Controllers/SuperAdmin/SchoolBillingAccessController.php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolBillingAccessController extends Controller
{
    public function edit(School $school): Response
    {
        return Inertia::render('SuperAdmin/Schools/BillingAccess', [
            'school' => $school,
            'rules' => config('billing_access.allowed_roles_by_stage'),
        ]);
    }

    public function update(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate([
            'billing_cycle_amount_due' => ['required', 'numeric', 'min:0'],
            'billing_cycle_amount_paid' => ['required', 'numeric', 'min:0'],
            'billing_grace_until' => ['nullable', 'date'],
            'billing_access_override' => ['required', 'boolean'],
            'billing_access_override_reason' => ['nullable', 'string', 'max:1000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $oldStage = $school->billing_access_stage;
        $newStage = $this->resolveStage((float) $data['billing_cycle_amount_due'], (float) $data['billing_cycle_amount_paid']);
        $percent = $this->calculatePercent((float) $data['billing_cycle_amount_due'], (float) $data['billing_cycle_amount_paid']);

        $school->update([
            'billing_cycle_amount_due' => $data['billing_cycle_amount_due'],
            'billing_cycle_amount_paid' => $data['billing_cycle_amount_paid'],
            'billing_paid_percent' => $percent,
            'billing_access_stage' => $newStage,
            'billing_grace_until' => $data['billing_grace_until'] ?? null,
            'billing_access_override' => (bool) $data['billing_access_override'],
            'billing_access_override_reason' => $data['billing_access_override_reason'] ?? null,
            'billing_restricted_at' => $newStage === 'full' ? null : now(),
        ]);

        $this->logAccessEvent(
            school: $school,
            oldStage: $oldStage,
            newStage: $newStage,
            reason: $data['reason'] ?? 'manual_update'
        );

        return back()->with('success', 'School billing access settings updated.');
    }

    public function recalculate(Request $request, School $school): RedirectResponse
    {
        $oldStage = $school->billing_access_stage;
        $newStage = $this->resolveStage((float) $school->billing_cycle_amount_due, (float) $school->billing_cycle_amount_paid);
        $percent = $this->calculatePercent((float) $school->billing_cycle_amount_due, (float) $school->billing_cycle_amount_paid);

        $school->update([
            'billing_paid_percent' => $percent,
            'billing_access_stage' => $newStage,
            'billing_restricted_at' => $newStage === 'full' ? null : now(),
        ]);

        $this->logAccessEvent(
            school: $school,
            oldStage: $oldStage,
            newStage: $newStage,
            reason: 'recalculate'
        );

        return back()->with('success', 'Billing access recalculated.');
    }

    private function calculatePercent(float $due, float $paid): float
    {
        if ($due <= 0) return 0;
        return min(100, round(($paid / $due) * 100, 2));
    }

    private function resolveStage(float $due, float $paid): string
    {
        if ($due <= 0) return 'locked';
        $percent = $this->calculatePercent($due, $paid);

        if ($percent >= 100) return 'full';
        if ($percent >= 50) return 'half_only';
        if ($percent >= 25) return 'quarter_only';

        return 'locked';
    }

    private function logAccessEvent(School $school, ?string $oldStage, string $newStage, string $reason): void
    {
        \DB::table('school_access_events')->insert([
            'school_id' => $school->id,
            'changed_by' => auth()->id(),
            'old_stage' => $oldStage,
            'new_stage' => $newStage,
            'amount_due' => $school->billing_cycle_amount_due,
            'amount_paid' => $school->billing_cycle_amount_paid,
            'paid_percent' => $school->billing_paid_percent,
            'override_enabled' => (bool) $school->billing_access_override,
            'override_reason' => $school->billing_access_override_reason,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}