<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\FinanceTransaction;
use App\Models\InventoryItem;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SchoolOperationsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->normalizeExistingUserRoles();
            $this->seedRolesAndUsers();
            $this->seedStudents();
            $this->seedFinanceTransactions();
            $this->seedStoreItems();
            $this->seedExamsAndResults();
        });
    }

    private function normalizeExistingUserRoles(): void
    {
        User::query()
            ->select('id', 'role')
            ->whereNotNull('role')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    $normalized = $this->canonicalRole($user->role);

                    if ($normalized !== $user->role) {
                        User::whereKey($user->id)->update(['role' => $normalized]);
                    }
                }
            });
    }

    private function canonicalRole(?string $role): string
    {
        $raw = strtolower(trim((string) $role));
        $key = str_replace(['-', ' '], '_', $raw);

        return match ($key) {
            'principal' => 'principal',
            'deputy_principal', 'deputyprincipal' => 'deputy_principal',
            'hod', 'head_of_department', 'headteacher_department' => 'hod',
            'accountant', 'school_accountant' => 'accountant',
            'store_keeper', 'storekeeper', 'store_keeper_' => 'store_keeper',
            'secretary', 'school_secretary' => 'secretary',
            default => $key,
        };
    }

    private function seedRolesAndUsers(): void
    {
        User::updateOrCreate(
            ['email' => 'principal@vigurungani.school'],
            ['name' => 'Abass Ulaya', 'password' => Hash::make('password'), 'role' => 'principal']
        );

        User::updateOrCreate(
            ['email' => 'deputy@vigurungani.school'],
            ['name' => 'Deputy Principal', 'password' => Hash::make('password'), 'role' => 'deputy_principal']
        );

        User::updateOrCreate(
            ['email' => 'hod@vigurungani.school'],
            ['name' => 'Head of Department', 'password' => Hash::make('password'), 'role' => 'hod']
        );

        User::updateOrCreate(
            ['email' => 'accountant@vigurungani.school'],
            ['name' => 'School Accountant', 'password' => Hash::make('password'), 'role' => 'accountant']
        );

        User::updateOrCreate(
            ['email' => 'storekeeper@vigurungani.school'],
            ['name' => 'School Store Keeper', 'password' => Hash::make('password'), 'role' => 'store_keeper']
        );

        User::updateOrCreate(
            ['email' => 'secretary@vigurungani.school'],
            ['name' => 'School Secretary', 'password' => Hash::make('password'), 'role' => 'secretary']
        );
    }

    private function seedStudents(): void
    {
        $students = [
            ['admission_no' => 'VSS001', 'first_name' => 'Amina', 'last_name' => 'Juma', 'parent_name' => 'Fatma Juma', 'parent_phone' => '0711000001', 'gender' => 'Female', 'education_system' => '8-4-4', 'class_level' => 'Form 1', 'pathway' => null, 'entry_marks' => 372, 'form_level' => 1, 'stream' => 'North', 'status' => 'active'],
            ['admission_no' => 'VSS002', 'first_name' => 'Brian', 'last_name' => 'Otieno', 'parent_name' => 'Peter Otieno', 'parent_phone' => '0711000002', 'gender' => 'Male', 'education_system' => '8-4-4', 'class_level' => 'Form 2', 'pathway' => null, 'entry_marks' => 340, 'form_level' => 2, 'stream' => 'East', 'status' => 'active'],
            ['admission_no' => 'VSS003', 'first_name' => 'Cynthia', 'last_name' => 'Mwende', 'parent_name' => 'Alice Mwende', 'parent_phone' => '0711000003', 'gender' => 'Female', 'education_system' => '8-4-4', 'class_level' => 'Form 3', 'pathway' => null, 'entry_marks' => 389, 'form_level' => 3, 'stream' => 'West', 'status' => 'active'],
            ['admission_no' => 'VSS004', 'first_name' => 'David', 'last_name' => 'Kipto', 'parent_name' => 'Samuel Kipto', 'parent_phone' => '0711000004', 'gender' => 'Male', 'education_system' => '8-4-4', 'class_level' => 'Form 4', 'pathway' => null, 'entry_marks' => 355, 'form_level' => 4, 'stream' => 'South', 'status' => 'active'],

            ['admission_no' => 'VSS005', 'first_name' => 'Esther', 'last_name' => 'Achieng', 'parent_name' => 'Lorna Achieng', 'parent_phone' => '0722000001', 'gender' => 'Female', 'education_system' => 'CBC', 'class_level' => 'Grade 10', 'pathway' => 'STEM', 'entry_marks' => 412, 'form_level' => 1, 'stream' => 'STEMA', 'status' => 'active'],
            ['admission_no' => 'VSS006', 'first_name' => 'Felix', 'last_name' => 'Muliso', 'parent_name' => 'David Muliso', 'parent_phone' => '0722000002', 'gender' => 'Male', 'education_system' => 'CBC', 'class_level' => 'Grade 10', 'pathway' => 'Social Sciences', 'entry_marks' => 367, 'form_level' => 1, 'stream' => 'SOCA', 'status' => 'active'],
            ['admission_no' => 'VSS007', 'first_name' => 'Grace', 'last_name' => 'Njeri', 'parent_name' => 'Lucy Njeri', 'parent_phone' => '0722000003', 'gender' => 'Female', 'education_system' => 'CBC', 'class_level' => 'Grade 11', 'pathway' => 'ARTS & Sports', 'entry_marks' => 391, 'form_level' => 2, 'stream' => 'ARTB', 'status' => 'active'],
            ['admission_no' => 'VSS008', 'first_name' => 'Hassan', 'last_name' => 'Ali', 'parent_name' => 'Asha Ali', 'parent_phone' => '0722000004', 'gender' => 'Male', 'education_system' => 'CBC', 'class_level' => 'Grade 11', 'pathway' => 'STEM', 'entry_marks' => 420, 'form_level' => 2, 'stream' => 'STEMB', 'status' => 'active'],
        ];

        foreach ($students as $student) {
            Student::withTrashed()->updateOrCreate(
                ['admission_no' => $student['admission_no']],
                array_merge($student, ['deleted_at' => null])
            );
        }
    }

    private function seedFinanceTransactions(): void
    {
        $transactions = [
            ['entry_date' => now()->subDays(25)->toDateString(), 'type' => 'income', 'category' => 'Tuition Fees', 'description' => 'Term collection', 'amount' => 120000, 'payment_method' => 'Bank', 'reference_no' => 'FIN-1001'],
            ['entry_date' => now()->subDays(20)->toDateString(), 'type' => 'expense', 'category' => 'Food Supplies', 'description' => 'Kitchen stock', 'amount' => 45000, 'payment_method' => 'Bank', 'reference_no' => 'FIN-1002'],
            ['entry_date' => now()->subDays(16)->toDateString(), 'type' => 'income', 'category' => 'Boarding Fees', 'description' => 'Boarding term fee', 'amount' => 90000, 'payment_method' => 'M-Pesa', 'reference_no' => 'FIN-1003'],
            ['entry_date' => now()->subDays(10)->toDateString(), 'type' => 'expense', 'category' => 'Utilities', 'description' => 'Water and power', 'amount' => 22000, 'payment_method' => 'Bank', 'reference_no' => 'FIN-1004'],
        ];

        foreach ($transactions as $tx) {
            FinanceTransaction::updateOrCreate(['reference_no' => $tx['reference_no']], $tx);
        }
    }

    private function seedStoreItems(): void
    {
        $items = [
            ['item_name' => 'Exercise Books', 'category' => 'Stationery', 'quantity' => 1200, 'unit' => 'pcs', 'reorder_level' => 300, 'notes' => 'For class use'],
            ['item_name' => 'A4 Printing Papers', 'category' => 'Stationery', 'quantity' => 800, 'unit' => 'reams', 'reorder_level' => 25, 'notes' => 'Office and exams'],
            ['item_name' => 'Laboratory Gloves', 'category' => 'Laboratory', 'quantity' => 500, 'unit' => 'pairs', 'reorder_level' => 150, 'notes' => 'Science practical'],
        ];

        $hasQuantity = Schema::hasColumn('inventory_items', 'quantity');
        $hasReorder = Schema::hasColumn('inventory_items', 'reorder_level');
        $hasNotes = Schema::hasColumn('inventory_items', 'notes');
        $hasUnit = Schema::hasColumn('inventory_items', 'unit');

        foreach ($items as $item) {
            $payload = [
                'item_name' => $item['item_name'],
                'category' => $item['category'],
            ];

            if ($hasQuantity) {
                $payload['quantity'] = $item['quantity'];
            }

            if ($hasUnit) {
                $payload['unit'] = $item['unit'];
            }

            if ($hasReorder) {
                $payload['reorder_level'] = $item['reorder_level'];
            }

            if ($hasNotes) {
                $payload['notes'] = $item['notes'];
            }

            InventoryItem::updateOrCreate(
                ['item_name' => $item['item_name'], 'category' => $item['category']],
                $payload
            );
        }
    }

    private function seedExamsAndResults(): void
    {
        $exam = Exam::updateOrCreate(
            ['title' => 'Mid-Term Assessment', 'term' => 'Term 1', 'year' => now()->year],
            [
                'exam_date' => now()->subDays(7)->toDateString(),
                'max_score' => 100,
                'status' => 'published',
                'assessment_system' => 'HYBRID',
                'class_level' => null,
                'pathway' => null,
            ]
        );

        $students = Student::query()->take(6)->get();

        foreach ($students as $index => $student) {
            $score = 58 + ($index * 6);

            ExamResult::updateOrCreate(
                ['exam_id' => $exam->id, 'student_id' => $student->id],
                [
                    'grading_system' => $student->education_system === 'CBC' ? 'CBC' : '844',
                    'score' => $score,
                    'grade' => $student->education_system === 'CBC'
                        ? null
                        : $this->grade844($score),
                    'points' => $student->education_system === 'CBC'
                        ? null
                        : $this->points844($this->grade844($score)),
                    'cbc_level' => $student->education_system === 'CBC'
                        ? $this->cbcLevelFromScore($score)
                        : null,
                    'cbc_comment' => $student->education_system === 'CBC'
                        ? 'Competency progression observed.'
                        : null,
                    'remarks' => 'Seeded result',
                ]
            );
        }
    }

    private function grade844(float $score): string
    {
        return match (true) {
            $score >= 80 => 'A',
            $score >= 75 => 'A-',
            $score >= 70 => 'B+',
            $score >= 65 => 'B',
            $score >= 60 => 'B-',
            $score >= 55 => 'C+',
            $score >= 50 => 'C',
            $score >= 45 => 'C-',
            $score >= 40 => 'D+',
            $score >= 35 => 'D',
            default => 'E',
        };
    }

    private function points844(string $grade): float
    {
        return match ($grade) {
            'A' => 12, 'A-' => 11, 'B+' => 10, 'B' => 9, 'B-' => 8,
            'C+' => 7, 'C' => 6, 'C-' => 5, 'D+' => 4, 'D' => 3, 'D-' => 2,
            default => 1,
        };
    }

    private function cbcLevelFromScore(float $score): string
    {
        return match (true) {
            $score >= 80 => 'EE',
            $score >= 65 => 'ME',
            $score >= 50 => 'AE',
            default => 'BE',
        };
    }
}