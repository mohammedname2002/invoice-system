<x-app-layout :title="'Statement · '.$customer->name">
    @php($period = $month || $year ? trim(($month ? \Illuminate\Support\Carbon::create(null, $month, 1)->format('F') : '').' '.($year ?? '')) : 'All time')

    <x-page-header :title="'Statement of account: '.$customer->name" :subtitle="$period">
        <x-slot:actions>
            <a href="{{ route('reports.index') }}" class="btn-secondary">Back to reports</a>
            <a href="{{ route('reports.statement.pdf', request()->only(['customer_id', 'month', 'year'])) }}" class="btn-primary">Download PDF</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-4">
        <x-stat-card label="Invoiced" :value="$totals['invoiced']->format()" />
        <x-stat-card label="Credited" :value="$totals['credited']->format()" />
        <x-stat-card label="Paid" :value="$totals['paid']->format()" tone="emerald" />
        <x-stat-card label="Balance" :value="$totals['balance']->formatWithCurrency()" :tone="$totals['balance']->isPositive() ? 'rose' : 'slate'" />
    </div>

    <section class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-th">Invoice</th>
                        <th class="table-th">Issued</th>
                        <th class="table-th">Due</th>
                        <th class="table-th text-right">Total</th>
                        <th class="table-th text-right">Credited</th>
                        <th class="table-th text-right">Paid</th>
                        <th class="table-th text-right">Balance</th>
                        <th class="table-th">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td class="table-td"><a href="{{ route('invoices.show', $invoice) }}" class="link">{{ $invoice->number }}</a></td>
                            <td class="table-td">{{ $invoice->issue_date->format('d M Y') }}</td>
                            <td class="table-td">{{ $invoice->due_date->format('d M Y') }}</td>
                            <td class="table-td text-right tabular-nums">{{ $invoice->total->format() }}</td>
                            <td class="table-td text-right tabular-nums">{{ $invoice->amount_credited->format() }}</td>
                            <td class="table-td text-right tabular-nums">{{ $invoice->amount_paid->format() }}</td>
                            <td class="table-td text-right font-medium tabular-nums">{{ $invoice->balance()->format() }}</td>
                            <td class="table-td"><x-status-badge :status="$invoice->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No invoices in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
