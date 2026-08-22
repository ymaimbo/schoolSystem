<?php

namespace Database\Seeders;

use App\Models\VoteHead;
use Illuminate\Database\Seeder;

class VoteHeadSeeder extends Seeder
{
    public function run(): void
    {
        $voteHeads = [
            ['code' => 'TEACHING',         'name' => 'Teaching, Learning Materials and Exams'],
            ['code' => 'RMI',              'name' => 'RMI / Remedial'],
            ['code' => 'LTT',              'name' => 'LT & T'],
            ['code' => 'ADM',              'name' => 'Administration Costs'],
            ['code' => 'EWC',              'name' => 'EWC'],
            ['code' => 'ACTIVITY',         'name' => 'Activity'],
            ['code' => 'PERSONAL_EMOL',    'name' => 'Personal Emoluments'],
            ['code' => 'MEDICAL_INSUR',    'name' => 'Medical & Insurance'],
            ['code' => 'SMASSE',           'name' => 'SMASSE'],
            ['code' => 'PTA',              'name' => 'PTA'],
            ['code' => 'LUNCH_PROGRAMME',  'name' => 'Lunch Programme'],
            ['code' => 'BOARDING_MEALS',   'name' => 'Boarding & Meals'],
        ];

        foreach ($voteHeads as $row) {
            VoteHead::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}