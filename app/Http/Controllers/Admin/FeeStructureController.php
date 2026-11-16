<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\FeeStructureLine;
use App\Models\VoteHead;
use App\Services\FeeAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FeeStructureController extends Controller
{
    public function __construct(
        private readonly FeeAllocationService $allocationService
    ) {
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Index
    // ─────────────────────────────────────────────────────────────────────

    public function index(Request $request): InertiaResponse
    {
        $schoolId = $this->currentSchoolId();

        $structures = FeeStructure::query()
            ->where('school_id', $schoolId)
            ->with(['lines' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->orderByDesc('year')
            ->orderBy('category')
            ->orderByDesc('id')
            ->get()
            ->map(function ($s) {
                $lines = collect($s->lines ?? []);

                $govtTotal   = (float) $lines->sum(fn ($l) => (float) ($l->govt_capitation_amount ?? 0));
                $parentTotal = (float) $lines->sum(fn ($l) => (float) $l->expectedParentAmount());

                $feeAccountsCount = $s->accounts()->count();

                return [
                    'id'                 => $s->id,
                    'school_id'          => $s->school_id,
                    'name'               => $s->name,
                    'year'               => $s->year,
                    'category'           => $s->category,
                    'class_level'        => $s->class_level,
                    'notes'              => $s->notes,
                    'approved_by'        => $s->approved_by,
                    'approved_at'        => $s->approved_at,
                    'fee_accounts_count' => $feeAccountsCount,
                    'govt_total'         => round($govtTotal, 2),
                    'parent_total'       => round($parentTotal, 2),
                    'total_amount'       => round($govtTotal + $parentTotal, 2),
                    'lines'              => $lines->map(fn ($l) => [
                        'id'                     => $l->id,
                        'fee_structure_id'       => $l->fee_structure_id,
                        'vote_head_id'           => $l->vote_head_id,
                        'vote_head_name'         => $l->voteHead?->name ?? null,
                        'vote_head_code'         => $l->voteHead?->code ?? null,
                        'govt_capitation_amount'  => (float) ($l->govt_capitation_amount ?? 0),
                        'parent_total_amount'    => (float) ($l->parent_total_amount ?? 0),
                        'term1_amount'           => (float) ($l->term1_amount ?? 0),
                        'term2_amount'           => (float) ($l->term2_amount ?? 0),
                        'term3_amount'           => (float) ($l->term3_amount ?? 0),
                        'total_amount'           => (float) ($l->total_amount ?? 0),
                        'sort_order'             => (int) ($l->sort_order ?? 0),
                    ])->values(),
                ];
            });

        $voteHeads = VoteHead::query()
            ->where('school_id', $schoolId)
            ->when(
                Schema::hasColumn('vote_heads', 'is_active'),
                fn ($q) => $q->where('is_active', true)
            )
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Finance/FeeStructures/Index', [
            'structures' => $structures,
            'voteHeads'  => $voteHeads,
            'categories' => $this->categories(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Store
    // ─────────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $schoolId   = $this->currentSchoolId();
        $categories = $this->categories();

        $validated = $request->validate([
            'name'                          => ['required', 'string', 'max:255'],
            'year'                          => ['required', 'integer', 'min:2000', 'max:2100'],
            'class_level'                   => ['nullable', 'string', 'max:255'],
            'category'                      => ['required', Rule::in($categories)],
            'notes'                         => ['nullable', 'string'],
            'lines'                         => ['required', 'array', 'min:1'],
            'lines.*.vote_head_id'          => ['required', 'integer',
                Rule::exists('vote_heads', 'id')->where('school_id', $schoolId)->where('is_active', true),
            ],
            'lines.*.govt_capitation_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.parent_total_amount'    => ['nullable', 'numeric', 'min:0'],
            'lines.*.term1_amount'           => ['nullable', 'numeric', 'min:0'],
            'lines.*.term2_amount'           => ['nullable', 'numeric', 'min:0'],
            'lines.*.term3_amount'           => ['nullable', 'numeric', 'min:0'],
            'lines.*.sort_order'             => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $schoolId) {
            $feeStructure = FeeStructure::create([
                'school_id'   => $schoolId,
                'name'        => $validated['name'],
                'year'        => $validated['year'],
                'category'    => $validated['category'],
                'class_level' => $validated['class_level'] ?? null,
                'notes'       => $validated['notes'] ?? null,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $lines = collect($validated['lines'])->values();

            foreach ($lines as $idx => $line) {
                $voteHeadId = (int) $line['vote_head_id'];
                if ($voteHeadId <= 0) continue;

                $govt   = round((float) ($line['govt_capitation_amount'] ?? 0), 2);
                $parent = round((float) ($line['parent_total_amount'] ?? 0), 2);
                $t1     = round((float) ($line['term1_amount'] ?? 0), 2);
                $t2     = round((float) ($line['term2_amount'] ?? 0), 2);
                $t3     = round((float) ($line['term3_amount'] ?? 0), 2);

                $termSum     = $t1 + $t2 + $t3;
                $totalAmount = max($parent, $termSum);

                $feeStructure->lines()->create([
                    'school_id'              => $schoolId,
                    'vote_head_id'           => $voteHeadId,
                    'govt_capitation_amount'  => $govt,
                    'parent_total_amount'    => $parent,
                    'term1_amount'           => $t1,
                    'term2_amount'           => $t2,
                    'term3_amount'           => $t3,
                    'total_amount'           => round($totalAmount, 2),
                    'sort_order'             => (int) ($line['sort_order'] ?? $idx + 1),
                ]);
            }
        });

        return back()->with('success', 'Fee structure created successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Edit
    // ─────────────────────────────────────────────────────────────────────

    public function edit(FeeStructure $feeStructure): InertiaResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $feeStructure->school_id === (int) $schoolId, 404);

        $feeStructure->load([
            'lines' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            'lines.voteHead:id,code,name',
        ]);

        $voteHeads = VoteHead::query()
            ->where('school_id', $schoolId)
            ->when(
                Schema::hasColumn('vote_heads', 'is_active'),
                fn ($q) => $q->where('is_active', true)
            )
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Finance/FeeStructures/Edit', [
            'structure'  => $feeStructure,
            'voteHeads'  => $voteHeads,
            'categories' => $this->categories(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Update (Gap 2 fix: history-safe line upsert)
    // ─────────────────────────────────────────────────────────────────────

    public function update(Request $request, FeeStructure $feeStructure): RedirectResponse
    {
        $schoolId   = $this->currentSchoolId();
        abort_unless((int) $feeStructure->school_id === (int) $schoolId, 404);

        $categories = $this->categories();

        $validated = $request->validate([
            'name'                          => ['required', 'string', 'max:255'],
            'year'                          => ['required', 'integer', 'min:2000', 'max:2100'],
            'class_level'                   => ['nullable', 'string', 'max:255'],
            'category'                      => ['required', Rule::in($categories)],
            'notes'                         => ['nullable', 'string'],
            'lines'                         => ['required', 'array', 'min:1'],
            'lines.*.vote_head_id'          => ['required', 'integer',
                Rule::exists('vote_heads', 'id')->where('school_id', $schoolId)->where('is_active', true),
            ],
            'lines.*.govt_capitation_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.parent_total_amount'    => ['nullable', 'numeric', 'min:0'],
            'lines.*.term1_amount'           => ['nullable', 'numeric', 'min:0'],
            'lines.*.term2_amount'           => ['nullable', 'numeric', 'min:0'],
            'lines.*.term3_amount'           => ['nullable', 'numeric', 'min:0'],
            'lines.*.sort_order'             => ['nullable', 'integer', 'min:0'],
        ]);

        // Check for guarded voteheads (those with ledger history)
        $guardedVoteheadIds  = $feeStructure->ledgeredLineVoteHeadIds();
        $incomingVoteheadIds = collect($validated['lines'])
            ->pluck('vote_head_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        $blockedRemovals = $guardedVoteheadIds->diff($incomingVoteheadIds);
        if ($blockedRemovals->isNotEmpty()) {
            return back()->withErrors([
                'lines' => 'Cannot remove voteheads that have payment history. (Blocked IDs: '
                    . $blockedRemovals->implode(', ') . ')',
            ]);
        }

        DB::transaction(function () use ($validated, $feeStructure, $schoolId) {
            $feeStructure->update([
                'name'        => $validated['name'],
                'year'        => $validated['year'],
                'category'    => $validated['category'],
                'class_level' => $validated['class_level'] ?? null,
                'notes'       => $validated['notes'] ?? null,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Safe upsert: match existing lines by vote_head_id
            $existingLines = $feeStructure->lines()->get()->keyBy('vote_head_id');
            $incomingIds   = collect();

            foreach ($validated['lines'] as $idx => $lineData) {
                $vhId = (int) $lineData['vote_head_id'];
                if ($vhId <= 0) continue;

                $incomingIds->push($vhId);

                $govt   = round((float) ($lineData['govt_capitation_amount'] ?? 0), 2);
                $parent = round((float) ($lineData['parent_total_amount'] ?? 0), 2);
                $t1     = round((float) ($lineData['term1_amount'] ?? 0), 2);
                $t2     = round((float) ($lineData['term2_amount'] ?? 0), 2);
                $t3     = round((float) ($lineData['term3_amount'] ?? 0), 2);

                $termSum     = $t1 + $t2 + $t3;
                $totalAmount = max($parent, $termSum);

                $payload = [
                    'school_id'              => $schoolId,
                    'vote_head_id'           => $vhId,
                    'govt_capitation_amount'  => $govt,
                    'parent_total_amount'    => $parent,
                    'term1_amount'           => $t1,
                    'term2_amount'           => $t2,
                    'term3_amount'           => $t3,
                    'total_amount'           => round($totalAmount, 2),
                    'sort_order'             => (int) ($lineData['sort_order'] ?? $idx + 1),
                ];

                $existing = $existingLines->get($vhId);
                if ($existing) {
                    $existing->update($payload);
                } else {
                    $feeStructure->lines()->create($payload);
                }
            }

            // Hard delete only lines we didn't keep
            // (guarded check already passed above)
            $feeStructure->lines()
                ->whereNotIn('vote_head_id', $incomingIds->toArray())
                ->delete();

            // Re-sync all assigned accounts to reflect new amounts
            foreach ($feeStructure->accounts as $account) {
                $this->allocationService->syncLedgersFromStructure($account, $feeStructure->id);
            }
        });

        // FIX: was 'finance.fee-structures.index' (missing admin. prefix)
        return redirect()
            ->route('admin.finance.fee-structures.index')
            ->with('success', 'Fee structure updated successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Destroy (Gap 2 fix: block when ledger history exists)
    // ─────────────────────────────────────────────────────────────────────

    public function destroy(FeeStructure $feeStructure): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $feeStructure->school_id === (int) $schoolId, 404);

        $feeAccountsCount = $feeStructure->accounts()->count();
        if ($feeAccountsCount > 0) {
            return back()->withErrors([
                'error' => 'Cannot delete structure — it is currently assigned to student accounts.',
            ]);
        }

        if ($feeStructure->ledgeredLineIds()->isNotEmpty()) {
            return back()->withErrors([
                'error' => 'Cannot delete structure — it has historical payment records attached to its lines.',
            ]);
        }

        DB::transaction(function () use ($feeStructure) {
            $feeStructure->lines()->delete();
            $feeStructure->delete();
        });

        return back()->with('success', 'Fee structure deleted successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Private Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function currentSchoolId(): int
    {
        $school = app('currentSchool');
        if (!$school || empty($school->id)) {
            abort(403, 'No school context found.');
        }
        return (int) $school->id;
    }

    private function categories(): array
    {
        if (Schema::hasTable('fee_structures') && Schema::hasColumn('fee_structures', 'category')) {
            try {
                $row = DB::selectOne("SHOW COLUMNS FROM `fee_structures` WHERE Field = 'category'");
                if ($row && preg_match_all("/'([^']+)'/", $row->Type, $matches)) {
                    return array_values(array_filter($matches[1], fn ($v) => trim($v) !== ''));
                }
            } catch (\Throwable $e) {
                // SQLite fallback
            }
        }

        return [
            'day_scholar',
            'boarder',
        ];
    }
}