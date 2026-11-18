<?php

namespace App\Services;

class GradingService
{
    /**
     * Get 8-4-4 Grade based on score.
     */
    public function get844Grade(float $score): string
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
            $score >= 30 => 'D-',
            default => 'E',
        };
    }

    /**
     * Get 8-4-4 Points based on grade.
     */
    public function get844Points(string $grade): float
    {
        return match (strtoupper($grade)) {
            'A'  => 12, 'A-' => 11,
            'B+' => 10, 'B'  => 9,  'B-' => 8,
            'C+' => 7,  'C'  => 6,  'C-' => 5,
            'D+' => 4,  'D'  => 3,  'D-' => 2,
            default => 1,
        };
    }

    /**
     * CBC Performance Levels Mapping.
     */
    public function getCbcLevelName(string $code): string
    {
        return match (strtoupper($code)) {
            'EE' => 'Exceeding Expectation',
            'ME' => 'Meeting Expectation',
            'AE' => 'Approaching Expectation',
            'BE' => 'Below Expectation',
            default => 'Not Evaluated',
        };
    }

    /**
     * Determine which results to save based on system.
     */
    public function formatResultData(array $data, string $system): array
    {
        if (strtolower($system) === 'cbc') {
            return [
                'grading_system' => 'cbc',
                'cbc_level'      => $data['cbc_level'] ?? null,
                'cbc_comment'    => $data['cbc_comment'] ?? null,
                'score'          => $data['score'] ?? null, // CBC can still have raw scores
                'grade'          => null,
                'points'         => null,
            ];
        }

        $score = (float)($data['score'] ?? 0);
        $grade = $data['grade'] ?? $this->get844Grade($score);

        return [
            'grading_system' => '844',
            'score'          => $score,
            'grade'          => $grade,
            'points'         => $this->get844Points($grade),
            'cbc_level'      => null,
            'cbc_comment'    => null,
        ];
    }
}