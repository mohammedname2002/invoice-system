<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): View
    {
        $validated = $request->validate(['year' => ['nullable', 'integer', 'between:2000,2100']]);
        $year = (int) ($validated['year'] ?? now()->year);

        return view('reports.index', [
            'year' => $year,
            'summary' => $this->reports->monthlySummary($year),
            'outstanding' => $this->reports->outstandingByCustomer(),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function statement(Request $request): View
    {
        return view('reports.statement', $this->statementData($request));
    }

    public function statementPdf(Request $request): Response
    {
        $data = $this->statementData($request);

        return Pdf::loadView('pdf.statement', $data)
            ->setPaper('a4')
            ->download('statement-'.str($data['customer']->name)->slug().'.pdf');
    }

    /** @return array<string, mixed> */
    private function statementData(Request $request): array
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        return $this->reports->statement(
            Customer::findOrFail($validated['customer_id']),
            isset($validated['month']) ? (int) $validated['month'] : null,
            isset($validated['year']) ? (int) $validated['year'] : null,
        );
    }
}
