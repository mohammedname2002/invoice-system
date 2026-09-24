<?php

namespace App\Console\Commands;

use App\Services\InvoiceService;
use Illuminate\Console\Command;

class FlagOverdueInvoices extends Command
{
    protected $signature = 'invoices:flag-overdue';

    protected $description = 'Mark unpaid and partially paid invoices past their due date as overdue';

    public function handle(InvoiceService $invoices): int
    {
        $count = $invoices->flagOverdue();

        $this->info("{$count} invoice(s) flagged as overdue.");

        return self::SUCCESS;
    }
}
