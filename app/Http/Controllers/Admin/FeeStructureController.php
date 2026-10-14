<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeProfile;
use App\Models\FeeStructure;
use App\Models\StudentFeeAccount;
use App\Models\VoteHead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FeeStructureController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $schoolId = $this->currentSchoolId();

        $structures = FeeStructure::query()
            ->where('school_id', $schoolId)
            ->with([
                'feeProfile:id,code,name',
                'lines:id,fee_structure_id,vote_head_id,govt_capitation_amount,parent_total_amount,term1_amount,term2_amount,term3_amount,total_amount,sort_order',
                'lines.voteHead:id,code,name',
            ])
            ->orderByDesc('year')
            ->orderBy('name')
            ->get()
            ->map(function (FeeStructure $s) use ($schoolId) {
                $lines = collect($s->lines ?? []);

                $govtTotal = (float) $lines->sum(fn ($l) => (float) ($l->govt_capitation_amount ?? 0));
                $parentTotal = (float) $lines->sum(fn ($l) => (float) ($l->parent_total_amount ?? 0));

                $accountsCount = StudentFeeAccount::query()
                    ->where('school_id', $schoolId)
                    ->where('fee_structure_id', $s->id)
                    ->count();

                return [
                    'id' => $s->id,
                    'school_id' => $s->school_id,
                    'name' => $s->name,
                    'year' => (int) $s->year,
                    'category' => $s->category,
                    'class_level' => $s->class_level,
                    'notes' => $s->notes,
                    'fee_profile_id' => $s->fee_profile_id,
                    'fee_profile_name' => $s->feeProfile?->name,
                    'fee_profile_code' => $s->feeProfile?->code,
                    'govt_total' => round($govtTotal, 2),
                    'parent_total' => round($parentTotal, 2),
                    'fee_accounts_count' => $accountsCount,
                    'lines' => $lines->map(fn ($l) => [
                        'id' => $l->id,
                        'fee_structure_id' => $l->fee_structure_id,
                        'vote_head_id' => $l->vote_head_id,
                        'vote_head_code' => $l->voteHead?->code,
                        'vote_head_name' => $l->voteHead?->name,
                        'govt_capitation_amount' => (float) ($l->govt_capitation_amount ?? 0),
                        'parent_total_amount' => (float) ($l->parent_total_amount ?? 0),
                        'term1_amount' => (float) ($l->term1_amount ?? 0),
                        'term2_amount' => (float) ($l->term2_amount ?? 0),
                        'term3_amount' => (float) ($l->term3_amount ?? 0),
                        'total_amount' => (float) ($l->total_amount ?? 0),
                        'sort_order' => (int) ($l->sort_order ?? 0),
                    ])->values(),
                ];
            })
            ->values();

        $voteHeads = VoteHead::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'is_active']);

        $feeProfiles = FeeProfile::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Admin/Finance/FeeStructures/Index', [
            'structures' => ['data' => $structures],
            'voteHeads' => $voteHeads,
            'feeProfiles' => $feeProfiles, // REQUIRED by UI
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();
        $categories = $this->categories();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],

            // REQUIRED per objective
            'fee_profile_id' => [
                'required',
                'integer',
                Rule::exists('fee_profiles', 'id')
                    ->where(fn ($q) => $q->where('school_id', $schoolId)->where('is_active', true)),
            ],

            'category' => ['required', 'string', Rule::in($categories)],
            'class_level' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.vote_head_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('vote_heads', 'id')
                    ->where(fn ($q) => $q->where('school_id', $schoolId)->where('is_active', true)),
            ],
            'lines.*.govt_capitation_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.parent_total_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.term1_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.term2_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.term3_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.total_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.sort_order' => ['nullable', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $schoolId) {
            $structure = FeeStructure::query()->create([
                'school_id' => $schoolId,
                'name' => $validated['name'],
                'year' => (int) $validated['year'],
                'fee_profile_id' => (int) $validated['fee_profile_id'],
                'category' => $validated['category'],
                'class_level' => $validated['class_level'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $lines = collect($validated['lines'] ?? [])
                ->map(function ($line, $idx) {
                    $sort = (int) ($line['sort_order'] ?? ($idx + 1));
                    return [
                        'vote_head_id' => (int) $line['vote_head_id'],
                        'govt_capitation_amount' => round((float) ($line['govt_capitation_amount'] ?? 0), 2),
                        'parent_total_amount' => round((float) ($line['parent_total_amount'] ?? 0), 2),
                        'term1_amount' => round((float) ($line['term1_amount'] ?? 0), 2),
                        'term2_amount' => round((float) ($line['term2_amount'] ?? 0), 2),
                        'term3_amount' => round((float) ($line['term3_amount'] ?? 0), 2),
                        'total_amount' => round((float) ($line['total_amount'] ?? 0), 2),
                        'sort_order' => $sort > 0 ? $sort : ($idx + 1),
                    ];
                })
                ->sortBy('sort_order')
                ->values();

            foreach ($lines as $index => $line) {
                $parent = (float) $line['parent_total_amount'];
                $termTotal = (float) $line['term1_amount'] + (float) $line['term2_amount'] + (float) $line['term3_amount'];
                $finalTotal = $parent > 0 ? $parent : $termTotal;

                $structure->lines()->create([
                    'school_id' => $schoolId,
                    'vote_head_id' => $line['vote_head_id'],
                    'govt_capitation_amount' => $line['govt_capitation_amount'],
                    'parent_total_amount' => $parent,
                    'term1_amount' => $line['term1_amount'],
                    'term2_amount' => $line['term2_amount'],
                    'term3_amount' => $line['term3_amount'],
                    'total_amount' => round($finalTotal, 2),
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return back()->with('success', 'Fee structure created successfully.');
    }

    public function edit(FeeStructure $feeStructure): InertiaResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $feeStructure->school_id === $schoolId, 404);

        $feeStructure->load([
            'feeProfile:id,code,name',
            'lines:id,fee_structure_id,vote_head_id,govt_capitation_amount,parent_total_amount,term1_amount,term2_amount,term3_amount,total_amount,sort_order',
            'lines.voteHead:id,code,name',
        ]);

        $voteHeads = VoteHead::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'is_active']);

        $feeProfiles = FeeProfile::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Admin/Finance/FeeStructures/Edit', [
            'structure' => $feeStructure,
            'voteHeads' => $voteHeads,
            'feeProfiles' => $feeProfiles, // REQUIRED by UI
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, FeeStructure $feeStructure): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $feeStructure->school_id === $schoolId, 404);

        $categories = $this->categories();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],

            // REQUIRED per objective
            'fee_profile_id' => [
                'required',
                'integer',
                Rule::exists('fee_profiles', 'id')
                    ->where(fn ($q) => $q->where('school_id', $schoolId)->where('is_active', true)),
            ],

            'category' => ['required', 'string', Rule::in($categories)],
            'class_level' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.vote_head_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('vote_heads', 'id')
                    ->where(fn ($q) => $q->where('school_id', $schoolId)->where('is_active', true)),
            ],
            'lines.*.govt_capitation_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.parent_total_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.term1_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.term2_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.term3_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.total_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.sort_order' => ['nullable', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $feeStructure, $schoolId) {
            $feeStructure->update([
                'name' => $validated['name'],
                'year' => (int) $validated['year'],
                'fee_profile_id' => (int) $validated['fee_profile_id'],
                'category' => $validated['category'],
                'class_level' => $validated['class_level'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $feeStructure->lines()->delete();

            $lines = collect($validated['lines'] ?? [])
                ->map(function ($line, $idx) {
                    $sort = (int) ($line['sort_order'] ?? ($idx + 1));
                    return [
                        'vote_head_id' => (int) $line['vote_head_id'],
                        'govt_capitation_amount' => round((float) ($line['govt_capitation_amount'] ?? 0), 2),
                        'parent_total_amount' => round((float) ($line['parent_total_amount'] ?? 0), 2),
                        'term1_amount' => round((float) ($line['term1_amount'] ?? 0), 2),
                        'term2_amount' => round((float) ($line['term2_amount'] ?? 0), 2),
                        'term3_amount' => round((float) ($line['term3_amount'] ?? 0), 2),
                        'total_amount' => round((float) ($line['total_amount'] ?? 0), 2),
                        'sort_order' => $sort > 0 ? $sort : ($idx + 1),
                    ];
                })
                ->sortBy('sort_order')
                ->values();

            foreach ($lines as $index => $line) {
                $parent = (float) $line['parent_total_amount'];
                $termTotal = (float) $line['term1_amount'] + (float) $line['term2_amount'] + (float) $line['term3_amount'];
                $finalTotal = $parent > 0 ? $parent : $termTotal;

                $feeStructure->lines()->create([
                    'school_id' => $schoolId,
                    'vote_head_id' => $line['vote_head_id'],
                    'govt_capitation_amount' => $line['govt_capitation_amount'],
                    'parent_total_amount' => $parent,
                    'term1_amount' => $line['term1_amount'],
                    'term2_amount' => $line['term2_amount'],
                    'term3_amount' => $line['term3_amount'],
                    'total_amount' => round($finalTotal, 2),
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return redirect()->route('finance.fee-structures.index')
            ->with('success', 'Fee structure updated successfully.');
    }

    public function destroy(FeeStructure $feeStructure): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $feeStructure->school_id === $schoolId, 404);

        $linkedAccounts = StudentFeeAccount::query()
            ->where('school_id', $schoolId)
            ->where('fee_structure_id', $feeStructure->id)
            ->count();

        if ($linkedAccounts > 0) {
            return back()->with('error', 'Cannot delete structure: it is already assigned to student accounts.');
        }

        DB::transaction(function () use ($feeStructure) {
            $feeStructure->lines()->delete();
            $feeStructure->delete();
        });

        return back()->with('success', 'Fee structure deleted successfully.');
    }

    private function currentSchoolId(): int
    {
        $school = app('currentSchool');
        if (!$school) {
            abort(403, 'No school context found.');
        }

        return (int) $school->id;
    }

    private function categories(): array
    {
        // Try enum values from DB first
        if (Schema::hasTable('fee_structures') && Schema::hasColumn('fee_structures', 'category')) {
            $row = DB::selectOne("SHOW COLUMNS FROM `fee_structures` WHERE Field = 'category'");
            $type = (string) ($row->Type ?? '');

            if (preg_match('/^enum\\((.*)\\)$/i', $type, $matches)) {
                $values = str_getcsv($matches[1], ',', "'");
                $values = array_values(array_filter(array_map('trim', $values)));
                if (!empty($values)) {
                    return $values;
                }
            }
        }

        // Fallback list
        return [
            'day_scholar',
            'boarder',
            'half_board',
            'sponsored_boarder',
            'candidate_class',
            'other',
        ];
    }
}