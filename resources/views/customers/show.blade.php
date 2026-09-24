<x-app-layout :title="$customer->name">
    <x-page-header :title="$customer->name" :subtitle="$customer->email">
        <x-slot:actions>
            @can('view-reports')
                <a href="{{ route('reports.statement', ['customer_id' => $customer->id]) }}" class="btn-secondary">Statement</a>
            @endcan
            @can('update', $customer)
                <a href="{{ route('customers.edit', $customer) }}" class="btn-secondary">Edit</a>
            @endcan
            @can('delete', $customer)
                <x-delete-button :action="route('customers.destroy', $customer)" confirm="Delete this customer?" />
            @endcan
            @can('create', App\Models\Invoice::class)
                <a href="{{ route('invoices.create', ['customer' => $customer->id]) }}" class="btn-primary">New invoice</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Outstanding" :value="$outstanding->formatWithCurrency()" :hint="$openCount.' open invoice(s)'" :tone="$outstanding->isPositive() ? 'rose' : 'slate'" />
        <x-stat-card label="Trade discount" :value="$customer->discount_rate.'%'" />
        <div class="card px-5 py-4 text-sm text-slate-600">
            <p class="font-medium text-slate-500">Details</p>
            <dl class="mt-2 space-y-1">
                @if ($customer->tax_number)<div><dt class="inline text-slate-500">TRN:</dt> <dd class="inline">{{ $customer->tax_number }}</dd></div>@endif
                @if ($customer->phone)<div><dt class="inline text-slate-500">Phone:</dt> <dd class="inline">{{ $customer->phone }}</dd></div>@endif
                @if ($customer->address)<div><dt class="inline text-slate-500">Address:</dt> <dd class="inline">{{ $customer->address }}</dd></div>@endif
            </dl>
        </div>
    </div>

    <section class="card overflow-hidden">
        <h2 class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">Invoices</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-th">Number</th>
                        <th class="table-th">Issued</th>
                        <th class="table-th">Due</th>
                        <th class="table-th text-right">Total</th>
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
                            <td class="table-td text-right tabular-nums">{{ $invoice->balance()->format() }}</td>
                            <td class="table-td"><x-status-badge :status="$invoice->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No invoices for this customer yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($invoices->hasPages())
            <div class="border-t border-slate-200 px-5 py-3">{{ $invoices->links() }}</div>
        @endif
    </section>
</x-app-layout>
