<?php

namespace Database\Seeders;

use App\Models\FeeStructure;
use App\Models\FeeStructureLine;
use App\Models\VoteHead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeeStructureSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Ensure vote heads exist before lines are inserted
            $this->call(VoteHeadSeeder::class);

            $dayScholar = FeeStructure::updateOrCreate(
                [
                    'year' => 2025,
                    'category' => 'day_scholar',
                    'name' => '2025 Day Scholars',
                ],
                [
                    'class_level' => null,
                    'notes' => 'Source: Official School Fee Structure Sheet',
                    'approved_by' => null,
                    'approved_at' => null,
                ]
            );

            $boarderGirls = FeeStructure::updateOrCreate(
                [
                    'year' => 2025,
                    'category' => 'boarder',
                    'name' => '2025 Boarders (Girls Only)',
                ],
                [
                    'class_level' => null,
                    'notes' => 'Source: Official School Fee Structure Sheet',
                    'approved_by' => null,
                    'approved_at' => null,
                ]
            );

            $this->syncLines($dayScholar->id, [
                // [code, govt, parent, t1, t2, t3, total]
                ['TEACHING',        4144, 0,     0,    0,    0,    4144],
                ['RMI',             5000, 0,     0,    0,    0,    5000],
                ['LTT',             2000, 0,     0,    0,    0,    2000],
                ['ADM',             1500, 0,     0,    0,    0,    1500],
                ['EWC',             1500, 0,     0,    0,    0,    1500],
                ['ACTIVITY',        1500, 0,     0,    0,    0,    1500],
                ['PERSONAL_EMOL',   4400, 0,     0,    0,    0,    4400],
                ['MEDICAL_INSUR',   2000, 0,     0,    0,    0,    2000],
                ['SMASSE',           200, 0,     0,    0,    0,     200],
                ['PTA',                0, 2000, 2000,  0,    0,    2000],
                ['LUNCH_PROGRAMME',    0, 18000, 6000, 6000, 6000, 18000],
            ]);

            $this->syncLines($boarderGirls->id, [
                // [code, govt, parent, t1, t2, t3, total]
                ['TEACHING',        4144, 0,     0,    0,    0,    4144],
                ['RMI',             5000, 2000, 1000, 700,  300,  7000],
                ['LTT',             2000, 4000, 2000, 1300, 700,  6000],
                ['ADM',             1500, 2535, 1200, 800,  535,  4035],
                ['EWC',             1500, 2500, 1500, 700,  300,  4000],
                ['ACTIVITY',        1500, 500,  500,  0,    0,    2000],
                ['PERSONAL_EMOL',   4400, 7000, 4000, 2000, 1000, 11400],
                ['MEDICAL_INSUR',   2000, 0,     0,    0,    0,    2000],
                ['SMASSE',           200, 0,     0,    0,    0,     200],
                ['PTA',                0, 2000, 2000,  0,    0,    2000],
                ['BOARDING_MEALS',     0, 22000,13000,7000, 5000, 23000],
            ]);
        });
    }

    private function syncLines(int $feeStructureId, array $rows): void
    {
        foreach ($rows as $index => $row) {
            [$code, $govt, $parent, $t1, $t2, $t3, $total] = $row;

            $voteHead = VoteHead::where('code', $code)->first();

            if (!$voteHead) {
                // Safety fallback in case VoteHeadSeeder wasn't run
                $voteHead = VoteHead::create([
                    'code' => $code,
                    'name' => $code,
                    'is_active' => true,
                ]);
            }

            FeeStructureLine::updateOrCreate(
                [
                    'fee_structure_id' => $feeStructureId,
                    'vote_head_id' => $voteHead->id,
                ],
                [
                    'govt_capitation_amount' => (float) $govt,
                    'parent_total_amount' => (float) $parent,
                    'term1_amount' => (float) $t1,
                    'term2_amount' => (float) $t2,
                    'term3_amount' => (float) $t3,
                    'total_amount' => (float) $total,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}