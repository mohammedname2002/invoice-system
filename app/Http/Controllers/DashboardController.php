<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports): View
    {
        return view('dashboard', [
            'stats' => $reports->dashboard(),
            'recentInvoices' => Invoice::with('customer')->latest('issue_date')->latest('id')->limit(6)->get(),
            'overdueInvoices' => Invoice::with('customer')
                ->where('status', InvoiceStatus::Overdue)
                ->orderBy('due_date')
                ->limit(6)
                ->get(),
        ]);
    }
}
