<?php

namespace App\Console\Commands;

use App\Models\SchoolInvoice;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'billing:mark-overdue-invoices';
    protected $description = 'Mark unpaid or partial school invoices as overdue when due date has passed';

    public function handle(): int
    {
        $updated = SchoolInvoice::query()
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('balance_amount', '>', 0)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $this->info("Updated {$updated} invoice(s) to overdue.");
        return self::SUCCESS;
    }
}