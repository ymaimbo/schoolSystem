<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\SchoolInvoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SchoolInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::query()
            ->where('status', 'active')
            ->get(['id', 'name', 'slug']);

        $year = now()->year;

        foreach ($schools as $school) {
            $this->seedAnnualSubscription($school->id, $school->slug, $year);
            $this->seedSmsInvoices($school->id, $school->slug);
        }
    }

    private function seedAnnualSubscription(int $schoolId, string $slug, int $year): void
    {
        $periodStart = Carbon::create($year, 1, 1)->toDateString();
        $periodEnd = Carbon::create($year, 12, 31)->toDateString();
        $dueDate = Carbon::create($year, 2, 15)->toDateString();
        $amount = 145000.00;

        SchoolInvoice::query()->updateOrCreate(
            ['reference_no' => strtoupper($slug).'-SUB-'.$year],
            [
                'school_id' => $schoolId,
                'item' => 'Annual School Platform Subscription',
                'category' => 'subscription',
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'due_date' => $dueDate,
                'invoiced_amount' => $amount,
                'balance_amount' => $amount,
                'status' => now()->toDateString() > $dueDate ? 'overdue' : 'unpaid',
                'notes' => 'Covers academics, finance, communication, staff, store and reporting modules.',
                'created_by' => null,
            ]
        );
    }

    private function seedSmsInvoices(int $schoolId, string $slug): void
    {
        for ($i = 0; $i < 6; $i++) {
            $date = now()->subMonths($i);
            $periodStart = $date->copy()->startOfMonth()->toDateString();
            $periodEnd = $date->copy()->endOfMonth()->toDateString();
            $dueDate = $date->copy()->endOfMonth()->addDays(7)->toDateString();
            $amount = (float) rand(1500, 8500);

            SchoolInvoice::query()->updateOrCreate(
                ['reference_no' => strtoupper($slug).'-SMS-'.$date->format('Ym')],
                [
                    'school_id' => $schoolId,
                    'item' => 'Bulk SMS Usage Charges',
                    'category' => 'sms',
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'due_date' => $dueDate,
                    'invoiced_amount' => $amount,
                    'balance_amount' => $amount,
                    'status' => now()->toDateString() > $dueDate ? 'overdue' : 'unpaid',
                    'notes' => 'Automated billing for outgoing SMS notifications.',
                    'created_by' => null,
                ]
            );
        }
    }
}