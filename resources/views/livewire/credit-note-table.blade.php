<div class="card overflow-hidden">
    <div class="border-b border-slate-200 p-4">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Credit note, invoice, customer or reason…" class="form-input sm:max-w-sm" aria-label="Search credit notes">
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="table-th">Number</th>
                    <th class="table-th">Invoice</th>
                    <th class="table-th">Customer</th>
                    <th class="table-th">Date</th>
                    <th class="table-th">Reason</th>
                    <th class="table-th text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($creditNotes as $creditNote)
                    <tr wire:key="credit-note-{{ $creditNote->id }}" class="hover:bg-slate-50">
                        <td class="table-td"><a href="{{ route('credit-notes.show', $creditNote) }}" class="link">{{ $creditNote->number }}</a></td>
                        <td class="table-td"><a href="{{ route('invoices.show', $creditNote->invoice) }}" class="link">{{ $creditNote->invoice->number }}</a></td>
                        <td class="table-td">{{ $creditNote->invoice->customer->name }}</td>
                        <td class="table-td">{{ $creditNote->issue_date->format('d M Y') }}</td>
                        <td class="table-td max-w-xs truncate">{{ $creditNote->reason ?? '—' }}</td>
                        <td class="table-td text-right tabular-nums">{{ $creditNote->total->format() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No credit notes found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($creditNotes->hasPages())
        <div class="border-t border-slate-200 px-4 py-3">{{ $creditNotes->links() }}</div>
    @endif
</div>
