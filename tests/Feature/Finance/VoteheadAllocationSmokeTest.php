<?php

namespace Tests\Feature\Finance;

use App\Models\Student;
use App\Models\StudentFeeAccount;
use App\Models\StudentFeePayment;
use App\Services\FeeAllocationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VoteheadAllocationSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('schools')) {
            $this->markTestSkipped('schools table not found.');
        }

        $schoolId = DB::table('schools')->value('id');
        if (!$schoolId) {
            $schoolId = $this->createSchoolRow();
        }

        $this->schoolId = (int) $schoolId;
    }

    public function test_votehead_allocation_priority_precedence_smoke(): void
    {
        $this->assertTablesExist([
            'students',
            'fee_profiles',
            'vote_heads',
            'fee_structures',
            'fee_structure_lines',
            'student_fee_accounts',
            'student_fee_ledgers',
            'student_fee_payments',
            'student_fee_allocations',
        ]);

        $service = app(FeeAllocationService::class);

        // 1) Create profile
        $profileId = $this->insertFiltered('fee_profiles', [
            'school_id' => $this->schoolId,
            'code' => 'QA-PROF-' . strtoupper(substr(md5(uniqid('', true)), 0, 6)),
            'name' => 'QA Profile',
            'description' => 'QA profile for votehead smoke test',
            'is_active' => 1,
        ]);

        // 2) Create voteheads
        [$vh1, $vh2, $vh3] = [
            $this->insertFiltered('vote_heads', [
                'school_id' => $this->schoolId,
                'code' => 'TUI-' . strtoupper(substr(md5(uniqid('', true)), 0, 4)),
                'name' => 'Tuition',
                'is_active' => 1,
            ]),
            $this->insertFiltered('vote_heads', [
                'school_id' => $this->schoolId,
                'code' => 'BRD-' . strtoupper(substr(md5(uniqid('', true)), 0, 4)),
                'name' => 'Boarding',
                'is_active' => 1,
            ]),
            $this->insertFiltered('vote_heads', [
                'school_id' => $this->schoolId,
                'code' => 'EXM-' . strtoupper(substr(md5(uniqid('', true)), 0, 4)),
                'name' => 'Exam',
                'is_active' => 1,
            ]),
        ];

        // 3) Create structure with valid enum-safe values
        $structureId = $this->insertFiltered('fee_structures', [
            'school_id' => $this->schoolId,
            'fee_profile_id' => $profileId,
            'name' => 'QA Structure',
            'year' => (int) date('Y'),
            'category' => $this->safeColumnValue('fee_structures', 'category', 'day_scholar'),
            'class_level' => $this->safeColumnValue('fee_structures', 'class_level', 'Grade 8'),
            'notes' => 'smoke',
        ]);

        // Structure order: vh1 -> vh2 -> vh3
        $this->insertFiltered('fee_structure_lines', [
            'school_id' => $this->schoolId,
            'fee_structure_id' => $structureId,
            'vote_head_id' => $vh1,
            'parent_total_amount' => 1000,
            'total_amount' => 1000,
            'term1_amount' => 0,
            'term2_amount' => 0,
            'term3_amount' => 0,
            'sort_order' => 1,
        ]);
        $this->insertFiltered('fee_structure_lines', [
            'school_id' => $this->schoolId,
            'fee_structure_id' => $structureId,
            'vote_head_id' => $vh2,
            'parent_total_amount' => 1000,
            'total_amount' => 1000,
            'term1_amount' => 0,
            'term2_amount' => 0,
            'term3_amount' => 0,
            'sort_order' => 2,
        ]);
        $this->insertFiltered('fee_structure_lines', [
            'school_id' => $this->schoolId,
            'fee_structure_id' => $structureId,
            'vote_head_id' => $vh3,
            'parent_total_amount' => 1000,
            'total_amount' => 1000,
            'term1_amount' => 0,
            'term2_amount' => 0,
            'term3_amount' => 0,
            'sort_order' => 3,
        ]);

        // CASE A: Manual override takes highest precedence
        [$studentA, $accountA] = $this->makeStudentAndAccount($profileId, $structureId);
        $service->syncLedgersFromStructure($accountA, $structureId);

        $planManual = $service->allocateAcrossVoteHeadLedgers(
            account: $accountA,
            paymentAmount: 1500,
            student: $studentA,
            priorityConstraints: ['vote_head_ids' => [$vh3, $vh2, $vh1]]
        );

        $this->assertSame('manual', $planManual['priority_source']);
        $this->assertNotEmpty($planManual['allocations']);
        $this->assertSame($vh3, (int) $planManual['allocations'][0]['vote_head_id']);

        $paymentA = $this->createPayment($studentA->id, $accountA->id, 1500);
        $service->applyAllocationsToPayment($paymentA, $planManual['allocations'], $this->schoolId, null);

        $firstManualAlloc = DB::table('student_fee_allocations')
            ->where('student_fee_payment_id', $paymentA->id)
            ->orderBy('allocation_order')
            ->orderBy('id')
            ->first();

        $this->assertNotNull($firstManualAlloc);
        $this->assertSame($vh3, (int) $firstManualAlloc->vote_head_id);

        // CASE B: Profile priority used when no manual override
        if (!Schema::hasTable('fee_profile_votehead_priorities')) {
            $this->markTestSkipped('fee_profile_votehead_priorities table not found.');
        }

        $this->insertFiltered('fee_profile_votehead_priorities', [
            'school_id' => $this->schoolId,
            'fee_profile_id' => $profileId,
            'vote_head_id' => $vh2,
            'priority_order' => 1,
            'is_required' => 1,
            'is_active' => 1,
        ]);
        $this->insertFiltered('fee_profile_votehead_priorities', [
            'school_id' => $this->schoolId,
            'fee_profile_id' => $profileId,
            'vote_head_id' => $vh3,
            'priority_order' => 2,
            'is_required' => 1,
            'is_active' => 1,
        ]);
        $this->insertFiltered('fee_profile_votehead_priorities', [
            'school_id' => $this->schoolId,
            'fee_profile_id' => $profileId,
            'vote_head_id' => $vh1,
            'priority_order' => 3,
            'is_required' => 1,
            'is_active' => 1,
        ]);

        [$studentB, $accountB] = $this->makeStudentAndAccount($profileId, $structureId);
        $service->syncLedgersFromStructure($accountB, $structureId);

        $planProfile = $service->allocateAcrossVoteHeadLedgers(
            account: $accountB,
            paymentAmount: 1500,
            student: $studentB,
            priorityConstraints: ['vote_head_ids' => []]
        );

        $this->assertSame('profile', $planProfile['priority_source']);
        $this->assertNotEmpty($planProfile['allocations']);
        $this->assertSame($vh2, (int) $planProfile['allocations'][0]['vote_head_id']);

        // CASE C: Structure sort_order fallback when no profile id
        [$studentC, $accountC] = $this->makeStudentAndAccount(null, $structureId);
        $service->syncLedgersFromStructure($accountC, $structureId);

        $planStructure = $service->allocateAcrossVoteHeadLedgers(
            account: $accountC,
            paymentAmount: 1500,
            student: $studentC,
            priorityConstraints: ['vote_head_ids' => []]
        );

        $this->assertContains($planStructure['priority_source'], ['structure', 'fallback']);
        $this->assertNotEmpty($planStructure['allocations']);
        $this->assertSame($vh1, (int) $planStructure['allocations'][0]['vote_head_id']);

        // Sanity: allocated + unallocated = payment
        $this->assertEquals(
            1500.00,
            round((float) $planStructure['allocated_total'] + (float) $planStructure['unallocated'], 2)
        );
    }

    private function makeStudentAndAccount(?int $feeProfileId, int $structureId): array
    {
        $studentId = $this->insertFiltered('students', [
            'school_id' => $this->schoolId,
            'admission_no' => 'QA-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'first_name' => 'QA',
            'last_name' => 'Student',
            'class_level' => $this->safeColumnValue('students', 'class_level', 'Grade 8'),
            'stream' => $this->safeColumnValue('students', 'stream', 'A'),
            'status' => $this->safeColumnValue('students', 'status', 'active'),
            'contact_preference' => $this->safeColumnValue('students', 'contact_preference', 'parent'),
            'gender' => $this->safeColumnValue('students', 'gender', 'male'),
            'fee_profile_id' => $feeProfileId,
        ]);

        $accountId = $this->insertFiltered('student_fee_accounts', [
            'school_id' => $this->schoolId,
            'student_id' => $studentId,
            'fee_structure_id' => $structureId,
            'total_fee_due' => 3000,
        ]);

        return [
            Student::query()->findOrFail($studentId),
            StudentFeeAccount::query()->findOrFail($accountId),
        ];
    }

    private function createPayment(int $studentId, int $accountId, float $amount): StudentFeePayment
    {
        $paymentId = $this->insertFiltered('student_fee_payments', [
            'school_id' => $this->schoolId,
            'student_id' => $studentId,
            'student_fee_account_id' => $accountId,
            'amount' => round($amount, 2),
            'payment_method' => $this->safeColumnValue('student_fee_payments', 'payment_method', 'cash'),
            'organization_name' => null,
            'organization_id' => null,
            'receipt_no' => 'QA-RCPT-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8)),
            'paid_at' => now()->toDateString(),
            'recorded_by' => null,
            'notes' => 'QA smoke payment',
        ]);

        return StudentFeePayment::query()->findOrFail($paymentId);
    }

    private function createSchoolRow(): int
    {
        return $this->insertFiltered('schools', [
            'name' => 'QA School',
            'code' => 'QA-' . strtoupper(substr(md5((string) microtime(true)), 0, 6)),
            'location' => $this->safeColumnValue('schools', 'location', 'QA Location'),
            'is_active' => 1,
        ]);
    }

    private function assertTablesExist(array $tables): void
    {
        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing required table: {$table}");
        }
    }

    private function insertFiltered(string $table, array $payload): int
    {
        $cols = Schema::getColumnListing($table);
        $data = array_intersect_key($payload, array_flip($cols));
        $data = $this->fillMissingRequiredColumns($table, $data);

        if (in_array('created_at', $cols, true) && !array_key_exists('created_at', $data)) {
            $data['created_at'] = now();
        }
        if (in_array('updated_at', $cols, true) && !array_key_exists('updated_at', $data)) {
            $data['updated_at'] = now();
        }

        return (int) DB::table($table)->insertGetId($data);
    }

    private function fillMissingRequiredColumns(string $table, array $data): array
    {
        $columns = DB::select("SHOW COLUMNS FROM `{$table}`");

        foreach ($columns as $col) {
            $field = (string) $col->Field;
            $type = strtolower((string) $col->Type);
            $nullable = strtoupper((string) $col->Null) === 'YES';
            $default = $col->Default;
            $extra = strtolower((string) $col->Extra);

            if (array_key_exists($field, $data)) continue;
            if ($field === 'id' || str_contains($extra, 'auto_increment')) continue;
            if ($nullable || $default !== null) continue;

            $data[$field] = $this->valueForRequiredColumn($table, $field, $type);
        }

        return $data;
    }

    private function valueForRequiredColumn(string $table, string $field, string $type): mixed
    {
        if (str_starts_with($type, 'enum(')) {
            return $this->firstEnumValue($table, $field, 'N/A');
        }

        if ($field === 'name') return 'QA';
        if ($field === 'code') return 'QA-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
        if ($field === 'location') return 'QA Location';
        if (str_contains($field, 'email')) return 'qa+' . substr(md5(uniqid('', true)), 0, 6) . '@example.com';
        if ($field === 'gender') return $this->safeColumnValue($table, $field, 'male');
        if ($field === 'status') return $this->safeColumnValue($table, $field, 'active');
        if ($field === 'contact_preference') return $this->safeColumnValue($table, $field, 'parent');
        if ($field === 'admission_no') return 'QA-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
        if ($field === 'receipt_no') return 'QA-RCPT-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
        if ($field === 'payment_method') return $this->safeColumnValue($table, $field, 'cash');
        if ($field === 'term') return $this->safeColumnValue($table, $field, 'ANNUAL');
        if ($field === 'source') return $this->safeColumnValue($table, $field, 'parent');

        if (str_contains($type, 'tinyint(1)') || str_starts_with($field, 'is_')) return 1;
        if (str_contains($type, 'int')) return 1;
        if (str_contains($type, 'decimal') || str_contains($type, 'float') || str_contains($type, 'double')) return 0;
        if (str_contains($type, 'date') && !str_contains($type, 'datetime') && !str_contains($type, 'timestamp')) return now()->toDateString();
        if (str_contains($type, 'datetime') || str_contains($type, 'timestamp')) return now();

        return 'QA';
    }

    private function safeColumnValue(string $table, string $column, mixed $fallback): mixed
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return $fallback;
        }

        $row = DB::selectOne("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);
        if (!$row) return $fallback;

        $type = strtolower((string) ($row->Type ?? ''));
        if (str_starts_with($type, 'enum(')) {
            return $this->firstEnumValue($table, $column, (string) $fallback);
        }

        return $fallback;
    }

    private function firstEnumValue(string $table, string $column, string $fallback): string
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return $fallback;
        }

        $row = DB::selectOne("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);
        if (!$row || empty($row->Type)) {
            return $fallback;
        }

        $type = (string) $row->Type;
        if (!preg_match('/^enum\((.*)\)$/i', $type, $matches)) {
            return $fallback;
        }

        $raw = $matches[1] ?? '';
        $parts = str_getcsv($raw, ',', "'");
        $first = $parts[0] ?? null;

        return ($first !== null && $first !== '') ? (string) $first : $fallback;
    }
}