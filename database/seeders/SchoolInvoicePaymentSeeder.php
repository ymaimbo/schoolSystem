<?php

namespace Database\Seeders;

use App\Models\SchoolInvoice;
use App\Models\SchoolInvoicePayment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SchoolInvoicePaymentSeeder extends Seeder
{
    public function run(): void
    {
        $invoices = SchoolInvoice::query()
            ->orderBy('id')
            ->get();

        DB::transaction(function () use ($invoices): void {
            foreach ($invoices as $invoice) {
                $invoicedAmount = (float) $invoice->invoiced_amount;

                if ($invoicedAmount <= 0) {
                    continue;
                }

                // 60% chance fully paid, 40% chance partial for realistic demo data.
                $isFullyPaid = (bool) random_int(0, 99) < 60;
                $targetPaid = $isFullyPaid
                    ? $invoicedAmount
                    : round($invoicedAmount * (random_int(30, 80) / 100), 2);

                if ($targetPaid <= 0) {
                    continue;
                }

                $instalments = random_int(1, 3);
                $remaining = $targetPaid;

                for ($i = 1; $i <= $instalments; $i++) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $amount = $i === $instalments
                        ? $remaining
                        : round($remaining * (random_int(25, 60) / 100), 2);

                    if ($amount <= 0) {
                        continue;
                    }

                    $paidAt = $this->resolvePaidAt($invoice, $i);

                    SchoolInvoicePayment::query()->create([
                        'school_id' => $invoice->school_id,
                        'school_invoice_id' => $invoice->id,
                        'amount' => $amount,
                        'method' => collect(['bank', 'mpesa', 'cash', 'cheque'])->random(),
                        'reference_no' => 'PMT-'.$invoice->id.'-'.$i.'-'.strtoupper(bin2hex(random_bytes(2))),
                        'paid_at' => $paidAt,
                        'notes' => 'Demo payment seeded for billing showcase.',
                        'received_by' => null,
                    ]);

                    $remaining = round($remaining - $amount, 2);
                }

                $paidSoFar = (float) SchoolInvoicePayment::query()
                    ->where('school_invoice_id', $invoice->id)
                    ->sum('amount');

                $newBalance = max(0, round($invoicedAmount - $paidSoFar, 2));
                $status = $this->resolveStatus($newBalance, $invoicedAmount, $invoice->due_date);

                $invoice->update([
                    'balance_amount' => $newBalance,
                    'status' => $status,
                ]);
            }
        });
    }

    private function resolvePaidAt(SchoolInvoice $invoice, int $index): Carbon
    {
        $base = $invoice->period_end
            ? Carbon::parse($invoice->period_end)
            : Carbon::parse($invoice->created_at);

        return $base->copy()->addDays($index * random_int(2, 10))->setTime(random_int(8, 17), random_int(0, 59));
    }

    private function resolveStatus(float $balance, float $invoiced, $dueDate): string
    {
        if ($balance <= 0) {
            return 'paid';
        }

        if ($balance < $invoiced) {
            if ($dueDate && now()->isAfter(Carbon::parse($dueDate))) {
                return 'overdue';
            }

            return 'partial';
        }

        if ($dueDate && now()->isAfter(Carbon::parse($dueDate))) {
            return 'overdue';
        }

        return 'unpaid';
    }
}