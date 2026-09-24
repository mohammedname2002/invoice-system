<x-app-layout title="Reports">
    <x-page-header title="Reports" :subtitle="'Figures in '.config('invoicing.currency').'.'">
        <x-slot:actions>
            <form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-2">
                <label for="year" class="text-sm text-slate-600">Year</label>
                <select id="year" name="year" class="form-input w-28" onchange="this.form.submit()">
                    @foreach (range(now()->year, now()->year - 5) as $option)
                        <option value="{{ $option }}" @selected($option === $year)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-5">
        <section class="card overflow-hidden lg:col-span-3">
            <h2 class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">Monthly summary {{ $year }}</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="table-th">Month</th>
                            <th class="table-th text-right">Invoiced</th>
                            <th class="table-th text-right">Credited</th>
                            <th class="table-th text-right">Collected</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($summary['months'] as $row)
                            <tr>
                                <td class="table-td">{{ \Illuminate\Support\Carbon::create($year, $row['month'], 1)->format('F') }}</td>
                                <td class="table-td text-right tabular-nums">{{ $row['invoiced']->format() }}</td>
                                <td class="table-td text-right tabular-nums">{{ $row['credited']->format() }}</td>
                                <td class="table-td text-right tabular-nums">{{ $row['collected']->format() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 font-semibold">
                        <tr>
                            <td class="table-td">Total</td>
                            <td class="table-td text-right tabular-nums">{{ $summary['totals']['invoiced']->format() }}</td>
                            <td class="table-td text-right tabular-nums">{{ $summary['totals']['credited']->format() }}</td>
                            <td class="table-td text-right tabular-nums">{{ $summary['totals']['collected']->format() }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <div class="space-y-6 lg:col-span-2">
            <section class="card p-6">
                <h2 class="text-sm font-semibold text-slate-900">Customer statement</h2>
                <p class="mt-1 text-sm text-slate-500">All invoices of a customer with credits, payments and balance; optionally for one month.</p>
                <form method="GET" action="{{ route('reports.statement') }}" class="mt-4 space-y-3">
                    <select name="customer_id" class="form-input" required aria-label="Customer">
                        <option value="">Select a customer…</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    <div class="grid grid-cols-2 gap-3">
                        <select name="month" class="form-input" aria-label="Month">
                            <option value="">All months</option>
                            @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}">{{ \Illuminate\Support\Carbon::create(null, $m, 1)->format('F') }}</option>
                            @endforeach
                        </select>
                        <select name="year" class="form-input" aria-label="Year">
                            <option value="">All years</option>
                            @foreach (range(now()->year, now()->year - 5) as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn-primary w-full">View statement</button>
                </form>
            </section>

            <section class="card overflow-hidden">
                <h2 class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">Outstanding by customer</h2>
                <ul class="divide-y divide-slate-100">
                    @forelse ($outstanding as $row)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <div>
                                <a href="{{ route('customers.show', $row['customer']) }}" class="link">{{ $row['customer']->name }}</a>
                                <p class="text-xs text-slate-500">{{ $row['open_invoices'] }} open invoice(s)@if ($row['overdue']->isPositive()) · <span class="text-rose-600">{{ $row['overdue']->format() }} overdue</span>@endif</p>
                            </div>
                            <span class="font-medium tabular-nums">{{ $row['balance']->format() }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-slate-500">Nothing outstanding.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-app-layout>
