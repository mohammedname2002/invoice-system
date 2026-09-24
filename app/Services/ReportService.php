<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Headline numbers for the dashboard.
     *
     * @return array{outstanding: Money, overdue: Money, overdue_count: int, invoiced_this_month: Money, collected_this_month: Money}
     */
    public function dashboard(?CarbonInterface $today = null): array
    {
        $today ??= Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $overdue = Invoice::query()->where('status', InvoiceStatus::Overdue);

        return [
            'outstanding' => $this->sumBalance(Invoice::query()->open()),
            'overdue' => $this->sumBalance(clone $overdue),
            'overdue_count' => $overdue->count(),
            'invoiced_this_month' => Money::ofMinor((int) Invoice::query()
                ->whereBetween('issue_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('total')),
            'collected_this_month' => Money::ofMinor((int) Payment::query()
                ->whereBetween('paid_at', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount')),
        ];
    }

    /**
     * Invoiced, credited and collected amounts per month of a year.
     *
     * Grouped in PHP rather than with database date functions, so the
     * report behaves the same on MySQL, MariaDB, PostgreSQL and SQLite.
     *
     * @return array{months: Collection<int, array{month: int, invoiced: Money, credited: Money, collected: Money}>, totals: array{invoiced: Money, credited: Money, collected: Money}}
     */
    public function monthlySummary(int $year): array
    {
        $byMonth = fn (Collection $rows, string $dateColumn, string $amountColumn): Collection => $rows
            ->groupBy(fn ($row) => (int) Carbon::parse($row->{$dateColumn})->month)
            ->map(fn (Collection $group) => Money::sum($group->pluck($amountColumn)));

        $invoiced = $byMonth(Invoice::query()->whereYear('issue_date', $year)->get(['issue_date', 'total']), 'issue_date', 'total');
        $credited = $byMonth(CreditNote::query()->whereYear('issue_date', $year)->get(['issue_date', 'total']), 'issue_date', 'total');
        $collected = $byMonth(Payment::query()->whereYear('paid_at', $year)->get(['paid_at', 'amount']), 'paid_at', 'amount');

        $months = collect(range(1, 12))->map(fn (int $month) => [
            'month' => $month,
            'invoiced' => $invoiced->get($month, Money::zero()),
            'credited' => $credited->get($month, Money::zero()),
            'collected' => $collected->get($month, Money::zero()),
        ]);

        return [
            'months' => $months,
            'totals' => [
                'invoiced' => Money::sum($months->pluck('invoiced')),
                'credited' => Money::sum($months->pluck('credited')),
                'collected' => Money::sum($months->pluck('collected')),
            ],
        ];
    }

    /**
     * Customers that still owe money, largest balance first.
     *
     * @return Collection<int, array{customer: Customer, balance: Money, overdue: Money, open_invoices: int}>
     */
    public function outstandingByCustomer(): Collection
    {
        return Invoice::query()
            ->open()
            ->with('customer')
            ->get()
            ->groupBy('customer_id')
            ->map(fn (Collection $invoices) => [
                'customer' => $invoices->first()->customer,
                'balance' => Money::sum($invoices->map->balance()),
                'overdue' => Money::sum($invoices->where('status', InvoiceStatus::Overdue)->map->balance()),
                'open_invoices' => $invoices->count(),
            ])
            ->sortByDesc(fn (array $row) => $row['balance']->minor)
            ->values();
    }

    /**
     * Statement of account for one customer, optionally limited to a month/year.
     *
     * @return array{customer: Customer, invoices: Collection<int, Invoice>, month: ?int, year: ?int, totals: array{invoiced: Money, credited: Money, paid: Money, balance: Money}}
     */
    public function statement(Customer $customer, ?int $month = null, ?int $year = null): array
    {
        $invoices = Invoice::query()
            ->forCustomer($customer->id)
            ->issuedIn($month, $year)
            ->orderBy('issue_date')
            ->orderBy('number')
            ->get();

        $invoiced = Money::sum($invoices->pluck('total'));
        $credited = Money::sum($invoices->pluck('amount_credited'));
        $paid = Money::sum($invoices->pluck('amount_paid'));

        return [
            'customer' => $customer,
            'invoices' => $invoices,
            'month' => $month,
            'year' => $year,
            'totals' => [
                'invoiced' => $invoiced,
                'credited' => $credited,
                'paid' => $paid,
                'balance' => $invoiced->subtract($credited)->subtract($paid),
            ],
        ];
    }

    /** @param  Builder<Invoice>  $query */
    private function sumBalance($query): Money
    {
        return Money::ofMinor((int) $query->sum(DB::raw('total - amount_credited - amount_paid')));
    }
}
