<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait BuildsExamPagePayload
{
    protected function examPagePayload(
        LengthAwarePaginator $exams,
        Collection $students,
        array $filters,
        string $role,
        array $permissions,
        array $classAssignments = [],
        array $subjectAssignments = [],
    ): array {
        return [
            'exams' => $this->normalizeExamPaginator($exams),
            'students' => $this->normalizeStudents($students),
            'filters' => [
                'year' => $filters['year'] ?? null,
                'term' => $filters['term'] ?? null,
                'status' => $filters['status'] ?? null,
                'assessment_system' => $filters['assessment_system'] ?? null,
                'class_level' => $filters['class_level'] ?? null,
            ],
            'role' => $this->normalizedRole($role),
            'permissions' => [
                'canCreateExams' => (bool) ($permissions['canCreateExams'] ?? false),
                'canDeleteExams' => (bool) ($permissions['canDeleteExams'] ?? false),
                'canUpdateAnyResult' => (bool) ($permissions['canUpdateAnyResult'] ?? false),
                'canUpdateClassResults' => (bool) ($permissions['canUpdateClassResults'] ?? false),
                'canUpdateSubjectResults' => (bool) ($permissions['canUpdateSubjectResults'] ?? false),
            ],
            'classAssignments' => array_values($classAssignments),
            'subjectAssignments' => array_values($subjectAssignments),
        ];
    }

    protected function normalizeExamPaginator(LengthAwarePaginator $paginator): array
    {
        $raw = $paginator->toArray();

        $raw['data'] = collect($raw['data'] ?? [])->map(function ($exam) {
            $results = collect($exam['results'] ?? [])->map(function ($result) {
                return [
                    'id' => $result['id'] ?? null,
                    'exam_id' => $result['exam_id'] ?? null,
                    'student_id' => $result['student_id'] ?? null,
                    'grading_system' => $result['grading_system'] ?? null,
                    'score' => $result['score'] ?? null,
                    'grade' => $result['grade'] ?? null,
                    'points' => $result['points'] ?? null,
                    'cbc_level' => $result['cbc_level'] ?? null,
                    'cbc_comment' => $result['cbc_comment'] ?? null,
                    'remarks' => $result['remarks'] ?? null,
                    'student' => [
                        'id' => data_get($result, 'student.id'),
                        'admission_no' => data_get($result, 'student.admission_no'),
                        'first_name' => data_get($result, 'student.first_name'),
                        'last_name' => data_get($result, 'student.last_name'),
                        'class_level' => data_get($result, 'student.class_level'),
                        'stream' => data_get($result, 'student.stream'),
                        'education_system' => data_get($result, 'student.education_system'),
                        'pathway' => data_get($result, 'student.pathway'),
                    ],
                ];
            })->values()->all();

            return [
                'id' => $exam['id'] ?? null,
                'title' => $exam['title'] ?? null,
                'term' => $exam['term'] ?? null,
                'year' => $exam['year'] ?? null,
                'exam_date' => $exam['exam_date'] ?? null,
                'max_score' => $exam['max_score'] ?? null,
                'status' => $exam['status'] ?? null,
                'assessment_system' => $exam['assessment_system'] ?? null,
                'class_level' => $exam['class_level'] ?? null,
                'stream' => $exam['stream'] ?? null,
                'subject' => $exam['subject'] ?? null,
                'pathway' => $exam['pathway'] ?? null,
                'results' => $results,
            ];
        })->values()->all();

        return $raw;
    }

    protected function normalizeStudents(Collection $students): array
    {
        return $students->map(fn ($student) => [
            'id' => $student->id ?? null,
            'admission_no' => $student->admission_no ?? null,
            'first_name' => $student->first_name ?? null,
            'last_name' => $student->last_name ?? null,
            'education_system' => $student->education_system ?? null,
            'class_level' => $student->class_level ?? null,
            'stream' => $student->stream ?? null,
            'pathway' => $student->pathway ?? null,
        ])->values()->all();
    }

    protected function normalizedRole(string $value): string
    {
        return str_replace('-', '_', strtolower(trim($value)));
    }
}