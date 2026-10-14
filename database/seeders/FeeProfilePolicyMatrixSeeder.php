<?php

namespace Database\Seeders;

use App\Models\FeeProfile;
use App\Models\FeeProfileVoteheadPriority;
use App\Models\School;
use App\Models\VoteHead;
use Illuminate\Database\Seeder;

class FeeProfilePolicyMatrixSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::query()->get();

        foreach ($schools as $school) {
            $schoolId = (int) $school->id;

            $dayScholar = FeeProfile::query()->firstOrCreate(
                ['school_id' => $schoolId, 'code' => 'DAY_SCHOLAR'],
                ['name' => 'Day Scholar', 'description' => 'Default day scholar billing profile', 'is_active' => true]
            );

            $boarder = FeeProfile::query()->firstOrCreate(
                ['school_id' => $schoolId, 'code' => 'BOARDER'],
                ['name' => 'Boarder', 'description' => 'Default boarder billing profile', 'is_active' => true]
            );

            $vhByCode = VoteHead::query()
                ->where('school_id', $schoolId)
                ->get()
                ->keyBy(fn ($v) => strtoupper((string) ($v->code ?? '')));

            // Helper: find by code first, else by name contains
            $resolveVoteHeadId = function (array $codes, array $nameContains = []) use ($vhByCode, $schoolId) {
                foreach ($codes as $c) {
                    $found = $vhByCode->get(strtoupper($c));
                    if ($found) return (int) $found->id;
                }

                if (!empty($nameContains)) {
                    $row = VoteHead::query()
                        ->where('school_id', $schoolId)
                        ->where(function ($q) use ($nameContains) {
                            foreach ($nameContains as $term) {
                                $q->orWhere('name', 'like', '%' . $term . '%');
                            }
                        })
                        ->first();
                    if ($row) return (int) $row->id;
                }

                return null;
            };

            $dayPriorityMatrix = [
                ['order' => 1, 'codes' => ['EXAM'], 'names' => ['exam']],
                ['order' => 2, 'codes' => ['TUITION'], 'names' => ['tuition']],
                ['order' => 3, 'codes' => ['LUNCH', 'MEALS'], 'names' => ['lunch', 'meal']],
                ['order' => 4, 'codes' => ['DEV', 'DEVELOPMENT'], 'names' => ['development']],
                ['order' => 5, 'codes' => ['ACTIVITY', 'ACTIVITIES'], 'names' => ['activity']],
            ];

            $boarderPriorityMatrix = [
                ['order' => 1, 'codes' => ['BOARD', 'BOARDING'], 'names' => ['boarding']],
                ['order' => 2, 'codes' => ['MEALS', 'LUNCH'], 'names' => ['meal', 'lunch']],
                ['order' => 3, 'codes' => ['EXAM'], 'names' => ['exam']],
                ['order' => 4, 'codes' => ['TUITION'], 'names' => ['tuition']],
                ['order' => 5, 'codes' => ['MEDICAL'], 'names' => ['medical']],
                ['order' => 6, 'codes' => ['DEV', 'DEVELOPMENT'], 'names' => ['development']],
                ['order' => 7, 'codes' => ['ACTIVITY', 'ACTIVITIES'], 'names' => ['activity']],
            ];

            foreach ($dayPriorityMatrix as $rule) {
                $voteHeadId = $resolveVoteHeadId($rule['codes'], $rule['names']);
                if (!$voteHeadId) continue;

                FeeProfileVoteheadPriority::query()->updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'fee_profile_id' => $dayScholar->id,
                        'vote_head_id' => $voteHeadId,
                    ],
                    [
                        'priority_order' => $rule['order'],
                        'is_required' => true,
                        'is_active' => true,
                    ]
                );
            }

            foreach ($boarderPriorityMatrix as $rule) {
                $voteHeadId = $resolveVoteHeadId($rule['codes'], $rule['names']);
                if (!$voteHeadId) continue;

                FeeProfileVoteheadPriority::query()->updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'fee_profile_id' => $boarder->id,
                        'vote_head_id' => $voteHeadId,
                    ],
                    [
                        'priority_order' => $rule['order'],
                        'is_required' => true,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}