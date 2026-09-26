<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DiagnoseAssignmentKeys extends Command
{
    /**
     * Command signature.
     *
     * --table can be used to inspect one specific table only.
     * Example: php artisan diagnose:assignment-keys --table=class_teacher_assignments
     */
    protected $signature = 'diagnose:assignment-keys {--table=}';

    /**
     * Command description.
     */
    protected $description = 'Diagnose which teacher key column exists in assignment tables';

    /**
     * Candidate teacher key columns by priority.
     */
    protected array $candidates = [
        'teacher_id',
        'user_id',
        'staff_id',
        'class_teacher_id',
    ];

    public function handle(): int
    {
        $singleTable = $this->option('table');

        $tables = $singleTable
            ? [$singleTable]
            : ['class_teacher_assignments', 'subject_teacher_assignments'];

        $rows = [];

        foreach ($tables as $table) {
            $rows[] = $this->diagnoseTable($table);
        }

        $this->table(
            ['Table', 'Exists', 'Detected Teacher Key', 'Candidate Presence', 'Notes'],
            array_map(function (array $row) {
                return [
                    $row['table'],
                    $row['exists'],
                    $row['detected_key'],
                    $row['candidate_presence'],
                    $row['notes'],
                ];
            }, $rows)
        );

        $missing = collect($rows)->contains(fn ($r) => $r['detected_key'] === 'NO_TEACHER_KEY_FOUND');

        if ($missing) {
            $this->warn('Some assignment tables exist but no known teacher key was found.');
            $this->line("Expected one of: " . implode(', ', $this->candidates));
            $this->line("Tip: add 'teacher_id' or map your controller to the detected schema.");
        } else {
            $this->info('Assignment key diagnosis complete.');
        }

        return self::SUCCESS;
    }

    protected function diagnoseTable(string $table): array
    {
        if (!Schema::hasTable($table)) {
            return [
                'table' => $table,
                'exists' => 'NO',
                'detected_key' => 'TABLE_MISSING',
                'candidate_presence' => '-',
                'notes' => 'Create table if required by role-scoped exam logic.',
            ];
        }

        $detected = null;
        $presenceBits = [];

        foreach ($this->candidates as $candidate) {
            $has = Schema::hasColumn($table, $candidate);
            $presenceBits[] = $candidate . ':' . ($has ? 'Y' : 'N');
            if ($has && $detected === null) {
                $detected = $candidate;
            }
        }

        return [
            'table' => $table,
            'exists' => 'YES',
            'detected_key' => $detected ?? 'NO_TEACHER_KEY_FOUND',
            'candidate_presence' => implode(', ', $presenceBits),
            'notes' => $detected
                ? "Use '{$detected}' in assignment queries."
                : 'No supported teacher key found.',
        ];
    }
}