<?php

namespace Database\Seeders;

use App\Models\VoteHead;
use Illuminate\Database\Seeder;

class VoteHeadSeeder extends Seeder
{
    public function run(): void
    {
        $voteHeads = [
            ['code' => 'TLME', 'name' => 'Teaching, Learning Materials and Exams'],
            ['code' => 'RMI', 'name' => 'RMI'],
            ['code' => 'LTT', 'name' => 'LT & T'],
            ['code' => 'ADM', 'name' => 'Administration Costs'],
            ['code' => 'EWC', 'name' => 'EWC'],
            ['code' => 'ACT', 'name' => 'Activity'],
            ['code' => 'PE', 'name' => 'Personal Emoluments'],
            ['code' => 'MED', 'name' => 'Medical & Insurance'],
            ['code' => 'SMASSE', 'name' => 'SMASSE'],
            ['code' => 'PTA', 'name' => 'PTA'],
            ['code' => 'LUNCH', 'name' => 'Lunch Programme'],
            ['code' => 'BOARDING', 'name' => 'Boarding & Meals'],
        ];

        foreach ($voteHeads as $vh) {
            VoteHead::updateOrCreate(
                ['code' => $vh['code']],
                ['name' => $vh['name'], 'is_active' => true]
            );
        }
    }
}