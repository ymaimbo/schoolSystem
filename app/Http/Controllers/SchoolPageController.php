<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class SchoolPageController extends Controller
{
    public function __invoke(School $school): Response
    {
        $schoolId = (int) $school->id;

        $studentsCount = $this->countWhereSchool('students', $schoolId);
        $staffCount = $this->countWhereSchool('staff_records', $schoolId);
        $attendanceRate = $this->attendanceRateToday($schoolId);
        $feeCollectionRate = $this->feeCollectionRate($schoolId);
        $examReadinessRate = $this->examReadinessRate($schoolId);

        return Inertia::render('Vigurungani', [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'slug' => $school->slug,
                'location' => $school->location,
                'county' => $school->county,
                'type' => $school->type,
                'status' => $school->status,
                'note' => $school->note,
                'code' => $school->code,
                'logo_path' => $school->logo_path,
                'hero_image_path' => $school->hero_image_path,
                'logo_url' => $school->logo_path ?: '/images/logo.png',
                'motto' => $school->note ?: 'A disciplined, data-driven school community focused on growth and accountability.',
            ],
            'metrics' => [
                'students_count' => $studentsCount,
                'staff_count' => $staffCount,
                'attendance_rate_today' => $attendanceRate,
                'fee_collection_rate' => $feeCollectionRate,
                'exam_readiness_rate' => $examReadinessRate,
            ],
        ]);
    }

    private function countWhereSchool(string $table, int $schoolId): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'school_id')) {
            return 0;
        }

        return (int) DB::table($table)->where('school_id', $schoolId)->count();
    }

    private function attendanceRateToday(int $schoolId): int
    {
        // Supports table: attendances with is_present boolean and school_id.
        if (! Schema::hasTable('attendances')
            || ! Schema::hasColumn('attendances', 'school_id')
            || ! Schema::hasColumn('attendances', 'is_present')) {
            return 0;
        }

        $query = DB::table('attendances')->where('school_id', $schoolId);

        if (Schema::hasColumn('attendances', 'attendance_date')) {
            $query->whereDate('attendance_date', now()->toDateString());
        } elseif (Schema::hasColumn('attendances', 'created_at')) {
            $query->whereDate('created_at', now()->toDateString());
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            return 0;
        }

        $present = (clone $query)->where('is_present', true)->count();

        return (int) round(($present / $total) * 100);
    }

    private function feeCollectionRate(int $schoolId): int
    {
        // Supports tables:
        // student_finance_accounts (expected_amount, school_id)
        // student_finance_payments (amount, school_id)
        if (! Schema::hasTable('student_finance_accounts')
            || ! Schema::hasTable('student_finance_payments')
            || ! Schema::hasColumn('student_finance_accounts', 'school_id')
            || ! Schema::hasColumn('student_finance_payments', 'school_id')) {
            return 0;
        }

        $expected = 0.0;
        if (Schema::hasColumn('student_finance_accounts', 'expected_amount')) {
            $expected = (float) DB::table('student_finance_accounts')
                ->where('school_id', $schoolId)
                ->sum('expected_amount');
        }

        $paid = 0.0;
        if (Schema::hasColumn('student_finance_payments', 'amount')) {
            $paid = (float) DB::table('student_finance_payments')
                ->where('school_id', $schoolId)
                ->sum('amount');
        }

        if ($expected <= 0) {
            return 0;
        }

        return (int) min(100, round(($paid / $expected) * 100));
    }

    private function examReadinessRate(int $schoolId): int
    {
        // Supports:
        // exams table (school_id)
        // exam_results table (school_id, exam_id OR no school_id but exam_id link)
        if (! Schema::hasTable('exams') || ! Schema::hasColumn('exams', 'school_id')) {
            return 0;
        }

        $totalExams = (int) DB::table('exams')->where('school_id', $schoolId)->count();
        if ($totalExams === 0) {
            return 0;
        }

        if (! Schema::hasTable('exam_results')) {
            return 0;
        }

        // Count exams that have at least one result posted.
        $examsWithResults = 0;

        if (Schema::hasColumn('exam_results', 'exam_id')) {
            $examIds = DB::table('exams')->where('school_id', $schoolId)->pluck('id');

            $resultsQuery = DB::table('exam_results')->whereIn('exam_id', $examIds);

            if (Schema::hasColumn('exam_results', 'school_id')) {
                $resultsQuery->where('school_id', $schoolId);
            }

            $examsWithResults = (int) $resultsQuery->distinct('exam_id')->count('exam_id');
        }

        return (int) round(($examsWithResults / $totalExams) * 100);
    }
}