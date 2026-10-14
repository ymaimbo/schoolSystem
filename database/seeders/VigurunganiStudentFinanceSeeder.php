<?php

namespace Database\Seeders;

use App\Models\FeeProfile;
use App\Models\FeeProfileVoteheadPriority;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentFeeAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VigurunganiStudentFinanceSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::query()
            ->whereRaw('LOWER(name) like ?', ['%vigurungani%'])
            ->first();

        if (!$school) {
            $this->command?->warn('No school matching "vigurungani" found. Seeder skipped.');
            return;
        }

        $schoolId = (int) $school->id;

        $dayProfile = FeeProfile::query()->where('school_id', $schoolId)->where('code', 'DAY_SCHOLAR')->first();
        $boardProfile = FeeProfile::query()->where('school_id', $schoolId)->where('code', 'BOARDER')->first();

        if (!$dayProfile && !$boardProfile) {
            $this->command?->warn('No fee profiles found for this school. Run FeeProfilePolicyMatrixSeeder first.');
            return;
        }

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->get();

        if ($students->isEmpty()) {
            $this->command?->warn('No students found for this school.');
            return;
        }

        $accountsCreated = 0;
        $profilesAssigned = 0;

        $hasLedgerTable = Schema::hasTable('student_fee_ledgers');
        $ledgerCols = $hasLedgerTable ? Schema::getColumnListing('student_fee_ledgers') : [];

        foreach ($students as $student) {
            DB::transaction(function () use (
                $student,
                $schoolId,
                $dayProfile,
                $boardProfile,
                $hasLedgerTable,
                $ledgerCols,
                &$accountsCreated,
                &$profilesAssigned
            ) {
                // 1) Assign profile if missing
                if (empty($student->fee_profile_id)) {
                    $isBoarderHint = str_contains(strtolower((string) ($student->stream ?? '')), 'board')
                        || str_contains(strtolower((string) ($student->class_level ?? '')), 'board');

                    $profile = $isBoarderHint
                        ? ($boardProfile ?? $dayProfile)
                        : ($dayProfile ?? $boardProfile);

                    if ($profile) {
                        $student->fee_profile_id = $profile->id;
                        $student->save();
                        $profilesAssigned++;
                    }
                }

                // 2) Create account if missing
                $account = StudentFeeAccount::query()->firstOrCreate(
                    [
                        'school_id' => $schoolId,
                        'student_id' => $student->id,
                    ],
                    [
                        'fee_structure_id' => null,
                        'total_fee_due' => 0,
                        'sponsor_org_name' => null,
                        'sponsor_org_id' => null,
                        'notes' => 'Auto-created by VigurunganiStudentFinanceSeeder',
                    ]
                );

                if ($account->wasRecentlyCreated) {
                    $accountsCreated++;
                }

                // 3) If no due exists, set starter due using profile
                $dueNow = (float) $account->total_fee_due;
                if ($dueNow == 0.0) {
                    $profileCode = optional($student->feeProfile)->code;
                    $defaultDue = $profileCode === 'BOARDER' ? 38000 : 24000;

                    $account->total_fee_due = $defaultDue;
                    $account->save();
                }

                // 4) Optional: create ledger rows if table exists and none exist yet
                if ($hasLedgerTable) {
                    $ledgerExists = DB::table('student_fee_ledgers')
                        ->where('student_fee_account_id', $account->id)
                        ->exists();

                    if (!$ledgerExists) {
                        $profileId = (int) ($student->fee_profile_id ?? 0);

                        $priorityRows = FeeProfileVoteheadPriority::query()
                            ->where('school_id', $schoolId)
                            ->where('fee_profile_id', $profileId)
                            ->where('is_active', true)
                            ->orderBy('priority_order')
                            ->get();

                        if ($priorityRows->isNotEmpty()) {
                            $lineAmount = round(((float) $account->total_fee_due) / max($priorityRows->count(), 1), 2);

                            foreach ($priorityRows as $idx => $row) {
                                $insert = [];

                                if (in_array('school_id', $ledgerCols, true)) {
                                    $insert['school_id'] = $schoolId;
                                }
                                if (in_array('student_fee_account_id', $ledgerCols, true)) {
                                    $insert['student_fee_account_id'] = $account->id;
                                }
                                if (in_array('vote_head_id', $ledgerCols, true)) {
                                    $insert['vote_head_id'] = $row->vote_head_id;
                                }
                                if (in_array('fee_structure_line_id', $ledgerCols, true)) {
                                    $insert['fee_structure_line_id'] = null;
                                }
                                if (in_array('term', $ledgerCols, true)) {
                                    $insert['term'] = 1;
                                }
                                if (in_array('parent_amount', $ledgerCols, true)) {
                                    $insert['parent_amount'] = $lineAmount;
                                }
                                if (in_array('parent_balance', $ledgerCols, true)) {
                                    $insert['parent_balance'] = $lineAmount;
                                }
                                if (in_array('balance_due', $ledgerCols, true)) {
                                    $insert['balance_due'] = $lineAmount;
                                }
                                if (in_array('amount_due', $ledgerCols, true)) {
                                    $insert['amount_due'] = $lineAmount;
                                }
                                if (in_array('outstanding_balance', $ledgerCols, true)) {
                                    $insert['outstanding_balance'] = $lineAmount;
                                }
                                if (in_array('term1_amount', $ledgerCols, true)) {
                                    $insert['term1_amount'] = round($lineAmount / 3, 2);
                                }
                                if (in_array('term2_amount', $ledgerCols, true)) {
                                    $insert['term2_amount'] = round($lineAmount / 3, 2);
                                }
                                if (in_array('term3_amount', $ledgerCols, true)) {
                                    $insert['term3_amount'] = round($lineAmount / 3, 2);
                                }
                                if (in_array('govt_capitation_amount', $ledgerCols, true)) {
                                    $insert['govt_capitation_amount'] = 0;
                                }
                                if (in_array('sort_order', $ledgerCols, true)) {
                                    $insert['sort_order'] = $idx + 1;
                                }
                                if (in_array('notes', $ledgerCols, true)) {
                                    $insert['notes'] = 'Auto-created by VigurunganiStudentFinanceSeeder';
                                }
                                if (in_array('created_at', $ledgerCols, true)) {
                                    $insert['created_at'] = now();
                                }
                                if (in_array('updated_at', $ledgerCols, true)) {
                                    $insert['updated_at'] = now();
                                }

                                if (!empty($insert)) {
                                    DB::table('student_fee_ledgers')->insert($insert);
                                }
                            }
                        }
                    }
                }
            });
        }

        $this->command?->info("Done: {$profilesAssigned} profiles assigned, {$accountsCreated} accounts created/initialized.");
    }
}