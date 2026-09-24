<div class="card overflow-hidden">
    <div class="border-b border-slate-200 p-4">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search by name, email or TRN…" class="form-input sm:max-w-sm" aria-label="Search customers">
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="table-th">Name</th>
                    <th class="table-th">Email</th>
                    <th class="table-th">TRN</th>
                    <th class="table-th text-right">Discount</th>
                    <th class="table-th text-right">Invoices</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($customers as $customer)
                    <tr wire:key="customer-{{ $customer->id }}" class="hover:bg-slate-50">
                        <td class="table-td"><a href="{{ route('customers.show', $customer) }}" class="link">{{ $customer->name }}</a></td>
                        <td class="table-td">{{ $customer->email ?? '—' }}</td>
                        <td class="table-td">{{ $customer->tax_number ?? '—' }}</td>
                        <td class="table-td text-right tabular-nums">{{ $customer->discount_rate }}%</td>
                        <td class="table-td text-right tabular-nums">{{ $customer->invoices_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No customers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($customers->hasPages())
        <div class="border-t border-slate-200 px-4 py-3">{{ $customers->links() }}</div>
    @endif
</div>
