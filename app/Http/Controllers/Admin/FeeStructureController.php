<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\FeeStructureLine;
use App\Models\StudentFeeAccount;
use App\Models\VoteHead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FeeStructureController extends Controller
{
    public function index(): Response
    {
        $structures = FeeStructure::query()
            ->withCount('feeAccounts')
            ->withSum('lines as govt_total', 'govt_capitation_amount')
            ->withSum('lines as parent_total', 'parent_total_amount')
            ->orderByDesc('year')
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $voteHeads = VoteHead::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Admin/Finance/FeeStructures/Index', [
            'structures' => $structures,
            'voteHeads' => $voteHeads,
            'categories' => ['day_scholar', 'boarder'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'category' => ['required', 'in:day_scholar,boarder'],
            'class_level' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.vote_head_id' => ['required', 'exists:vote_heads,id'],
            'lines.*.govt_capitation_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.parent_total_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.term1_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.term2_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.term3_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            $structure = FeeStructure::create([
                'name' => $data['name'],
                'year' => $data['year'],
                'category' => $data['category'],
                'class_level' => $data['class_level'] ?? null,
                'notes' => $data['notes'] ?? null,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            foreach ($data['lines'] as $i => $line) {
                $govt = (float) ($line['govt_capitation_amount'] ?? 0);
                $parent = (float) ($line['parent_total_amount'] ?? 0);
                $t1 = (float) ($line['term1_amount'] ?? 0);
                $t2 = (float) ($line['term2_amount'] ?? 0);
                $t3 = (float) ($line['term3_amount'] ?? 0);

                FeeStructureLine::create([
                    'fee_structure_id' => $structure->id,
                    'vote_head_id' => (int) $line['vote_head_id'],
                    'govt_capitation_amount' => $govt,
                    'parent_total_amount' => $parent,
                    'term1_amount' => $t1,
                    'term2_amount' => $t2,
                    'term3_amount' => $t3,
                    'total_amount' => $govt + $parent,
                    'sort_order' => (int) ($line['sort_order'] ?? ($i + 1)),
                ]);
            }
        });

        return back()->with('success', 'Fee structure created.');
    }

    public function edit(FeeStructure $feeStructure): Response
    {
        $feeStructure->load(['lines.voteHead']);

        $voteHeads = VoteHead::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Admin/Finance/FeeStructures/Edit', [
            'structure' => [
                'id' => $feeStructure->id,
                'name' => $feeStructure->name,
                'year' => $feeStructure->year,
                'category' => $feeStructure->category,
                'class_level' => $feeStructure->class_level,
                'notes' => $feeStructure->notes,
                'lines' => $feeStructure->lines->map(fn ($l) => [
                    'id' => $l->id,
                    'vote_head_id' => $l->vote_head_id,
                    'vote_head_code' => $l->voteHead?->code,
                    'vote_head_name' => $l->voteHead?->name,
                    'govt_capitation_amount' => (float) $l->govt_capitation_amount,
                    'parent_total_amount' => (float) $l->parent_total_amount,
                    'term1_amount' => (float) $l->term1_amount,
                    'term2_amount' => (float) $l->term2_amount,
                    'term3_amount' => (float) $l->term3_amount,
                    'total_amount' => (float) $l->total_amount,
                    'sort_order' => $l->sort_order,
                ])->values(),
            ],
            'voteHeads' => $voteHeads,
            'categories' => ['day_scholar', 'boarder'],
        ]);
    }

    public function update(Request $request, FeeStructure $feeStructure): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'category' => ['required', 'in:day_scholar,boarder'],
            'class_level' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.vote_head_id' => ['required', 'exists:vote_heads,id'],
            'lines.*.govt_capitation_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.parent_total_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.term1_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.term2_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.term3_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $feeStructure) {
            $feeStructure->update([
                'name' => $data['name'],
                'year' => $data['year'],
                'category' => $data['category'],
                'class_level' => $data['class_level'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $feeStructure->lines()->delete();

            foreach ($data['lines'] as $i => $line) {
                $govt = (float) ($line['govt_capitation_amount'] ?? 0);
                $parent = (float) ($line['parent_total_amount'] ?? 0);
                $t1 = (float) ($line['term1_amount'] ?? 0);
                $t2 = (float) ($line['term2_amount'] ?? 0);
                $t3 = (float) ($line['term3_amount'] ?? 0);

                FeeStructureLine::create([
                    'fee_structure_id' => $feeStructure->id,
                    'vote_head_id' => (int) $line['vote_head_id'],
                    'govt_capitation_amount' => $govt,
                    'parent_total_amount' => $parent,
                    'term1_amount' => $t1,
                    'term2_amount' => $t2,
                    'term3_amount' => $t3,
                    'total_amount' => $govt + $parent,
                    'sort_order' => (int) ($line['sort_order'] ?? ($i + 1)),
                ]);
            }
        });

        return redirect()
            ->route('admin.finance.fee-structures.index')
            ->with('success', 'Fee structure updated.');
    }

    public function destroy(FeeStructure $feeStructure): RedirectResponse
    {
        $inUse = StudentFeeAccount::query()
            ->where('fee_structure_id', $feeStructure->id)
            ->exists();

        if ($inUse) {
            return back()->with('error', 'Cannot delete. This structure is assigned to student accounts.');
        }

        $feeStructure->delete();

        return back()->with('success', 'Fee structure deleted.');
    }
}