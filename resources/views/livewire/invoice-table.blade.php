<div class="card overflow-hidden">
    <div class="grid gap-3 border-b border-slate-200 p-4 sm:grid-cols-2 lg:grid-cols-6">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Number or customer…" class="form-input lg:col-span-2" aria-label="Search invoices">

        <select wire:model.live="customerId" class="form-input" aria-label="Customer">
            <option value="">All customers</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="status" class="form-input" aria-label="Status">
            <option value="">Any status</option>
            @foreach ($statuses as $option)
                <option value="{{ $option->value }}">{{ $option->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="month" class="form-input" aria-label="Month">
            <option value="">Any month</option>
            @foreach (range(1, 12) as $m)
                <option value="{{ $m }}">{{ \Illuminate\Support\Carbon::create(null, $m, 1)->format('F') }}</option>
            @endforeach
        </select>

        <div class="flex gap-2">
            <select wire:model.live="year" class="form-input" aria-label="Year">
                <option value="">Any year</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
            <button type="button" wire:click="clearFilters" class="btn-secondary shrink-0" title="Clear filters">Clear</button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="table-th">Number</th>
                    <th class="table-th">Customer</th>
                    <th class="table-th">Issued</th>
                    <th class="table-th">Due</th>
                    <th class="table-th text-right">Total</th>
                    <th class="table-th text-right">Balance</th>
                    <th class="table-th">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($invoices as $invoice)
                    <tr wire:key="invoice-{{ $invoice->id }}" class="hover:bg-slate-50">
                        <td class="table-td"><a href="{{ route('invoices.show', $invoice) }}" class="link">{{ $invoice->number }}</a></td>
                        <td class="table-td">{{ $invoice->customer->name }}</td>
                        <td class="table-td">{{ $invoice->issue_date->format('d M Y') }}</td>
                        <td class="table-td">{{ $invoice->due_date->format('d M Y') }}</td>
                        <td class="table-td text-right tabular-nums">{{ $invoice->total->format() }}</td>
                        <td class="table-td text-right tabular-nums">{{ $invoice->balance()->format() }}</td>
                        <td class="table-td"><x-status-badge :status="$invoice->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No invoices match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($invoices->hasPages())
        <div class="border-t border-slate-200 px-4 py-3">{{ $invoices->links() }}</div>
    @endif
</div>
