<x-app-layout title="Dashboard">
    <x-page-header title="Dashboard" subtitle="Receivables at a glance.">
        <x-slot:actions>
            @can('create', App\Models\Invoice::class)
                <a href="{{ route('invoices.create') }}" class="btn-primary">New invoice</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Outstanding" :value="$stats['outstanding']->formatWithCurrency()" hint="Open invoices, net of credit notes and payments" />
        <x-stat-card label="Overdue" :value="$stats['overdue']->formatWithCurrency()" :hint="$stats['overdue_count'].' invoice(s) past due'" tone="rose" />
        <x-stat-card label="Invoiced this month" :value="$stats['invoiced_this_month']->formatWithCurrency()" tone="indigo" />
        <x-stat-card label="Collected this month" :value="$stats['collected_this_month']->formatWithCurrency()" tone="emerald" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Recent invoices</h2>
                <a href="{{ route('invoices.index') }}" class="text-sm link">View all</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($recentInvoices as $invoice)
                    <li class="flex items-center justify-between gap-4 px-5 py-3">
                        <div class="min-w-0">
                            <a href="{{ route('invoices.show', $invoice) }}" class="text-sm link">{{ $invoice->number }}</a>
                            <p class="truncate text-xs text-slate-500">{{ $invoice->customer->name }} · {{ $invoice->issue_date->format('d M Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm tabular-nums text-slate-700">{{ $invoice->total->format() }}</span>
                            <x-status-badge :status="$invoice->status" />
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-500">No invoices yet.</li>
                @endforelse
            </ul>
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Overdue</h2>
                <a href="{{ route('invoices.index', ['status' => 'overdue']) }}" class="text-sm link">View all</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($overdueInvoices as $invoice)
                    <li class="flex items-center justify-between gap-4 px-5 py-3">
                        <div class="min-w-0">
                            <a href="{{ route('invoices.show', $invoice) }}" class="text-sm link">{{ $invoice->number }}</a>
                            <p class="truncate text-xs text-slate-500">{{ $invoice->customer->name }} · due {{ $invoice->due_date->format('d M Y') }} ({{ $invoice->due_date->diffForHumans() }})</p>
                        </div>
                        <span class="text-sm font-medium tabular-nums text-rose-600">{{ $invoice->balance()->format() }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-500">Nothing overdue.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-app-layout>
