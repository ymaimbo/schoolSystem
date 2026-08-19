<?php

namespace Database\Seeders;

use App\Models\FeeStructure;
use App\Models\FeeStructureLine;
use App\Models\VoteHead;
use Illuminate\Database\Seeder;

class FeeStructureSeeder extends Seeder
{
    public function run(): void
    {
        $voteIds = VoteHead::query()->pluck('id', 'code');

        $day = FeeStructure::updateOrCreate(
            ['name' => '2026 Day Scholars', 'year' => 2026, 'category' => 'day_scholar'],
            ['class_level' => null, 'notes' => 'Based on approved 2026 day scholars fee sheet.']
        );

        $this->upsertLine($day->id, $voteIds['TLME'] ?? null, 4144, 0, 0, 0, 0, 4144, 1);
        $this->upsertLine($day->id, $voteIds['RMI'] ?? null, 5000, 0, 0, 0, 0, 5000, 2);
        $this->upsertLine($day->id, $voteIds['LTT'] ?? null, 2000, 0, 0, 0, 0, 2000, 3);
        $this->upsertLine($day->id, $voteIds['ADM'] ?? null, 1500, 0, 0, 0, 0, 1500, 4);
        $this->upsertLine($day->id, $voteIds['EWC'] ?? null, 1500, 0, 0, 0, 0, 1500, 5);
        $this->upsertLine($day->id, $voteIds['ACT'] ?? null, 1500, 0, 0, 0, 0, 1500, 6);
        $this->upsertLine($day->id, $voteIds['PE'] ?? null, 4400, 0, 0, 0, 0, 4400, 7);
        $this->upsertLine($day->id, $voteIds['MED'] ?? null, 2000, 0, 0, 0, 0, 2000, 8);
        $this->upsertLine($day->id, $voteIds['SMASSE'] ?? null, 200, 0, 0, 0, 0, 200, 9);
        $this->upsertLine($day->id, $voteIds['PTA'] ?? null, 0, 2000, 2000, 0, 0, 2000, 10);
        $this->upsertLine($day->id, $voteIds['LUNCH'] ?? null, 0, 18000, 6000, 6000, 6000, 18000, 11);

        $boarder = FeeStructure::updateOrCreate(
            ['name' => '2026 Boarders', 'year' => 2026, 'category' => 'boarder'],
            ['class_level' => null, 'notes' => 'Based on approved 2026 boarders fee sheet.']
        );

        $this->upsertLine($boarder->id, $voteIds['TLME'] ?? null, 4144, 0, 0, 0, 0, 4144, 1);
        $this->upsertLine($boarder->id, $voteIds['RMI'] ?? null, 5000, 2000, 1000, 700, 300, 7000, 2);
        $this->upsertLine($boarder->id, $voteIds['LTT'] ?? null, 2000, 4000, 2000, 1300, 700, 6000, 3);
        $this->upsertLine($boarder->id, $voteIds['ADM'] ?? null, 1500, 2535, 1200, 800, 535, 4035, 4);
        $this->upsertLine($boarder->id, $voteIds['EWC'] ?? null, 1500, 2500, 1500, 700, 300, 4000, 5);
        $this->upsertLine($boarder->id, $voteIds['ACT'] ?? null, 1500, 500, 500, 0, 0, 2000, 6);
        $this->upsertLine($boarder->id, $voteIds['PE'] ?? null, 4400, 7000, 4000, 2000, 1000, 11400, 7);
        $this->upsertLine($boarder->id, $voteIds['MED'] ?? null, 2000, 0, 0, 0, 0, 2000, 8);
        $this->upsertLine($boarder->id, $voteIds['SMASSE'] ?? null, 200, 0, 0, 0, 0, 200, 9);
        $this->upsertLine($boarder->id, $voteIds['PTA'] ?? null, 0, 2000, 2000, 0, 0, 2000, 10);

        // NOTE: Parent amount set to 25000 to match term split 13000+7000+5000
        $this->upsertLine($boarder->id, $voteIds['BOARDING'] ?? null, 0, 25000, 13000, 7000, 5000, 23000, 11);
    }

    private function upsertLine(
        int $feeStructureId,
        ?int $voteHeadId,
        float $govtCapitation,
        float $parentTotal,
        float $term1,
        float $term2,
        float $term3,
        float $total,
        int $sortOrder
    ): void {
        if (!$voteHeadId) {
            return;
        }

        FeeStructureLine::updateOrCreate(
            [
                'fee_structure_id' => $feeStructureId,
                'vote_head_id' => $voteHeadId,
            ],
            [
                'govt_capitation_amount' => $govtCapitation,
                'parent_total_amount' => $parentTotal,
                'term1_amount' => $term1,
                'term2_amount' => $term2,
                'term3_amount' => $term3,
                'total_amount' => $total,
                'sort_order' => $sortOrder,
            ]
        );
    }
}