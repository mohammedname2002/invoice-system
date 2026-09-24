<x-app-layout :title="$creditNote->number">
    <x-page-header :title="$creditNote->number">
        <x-slot:subtitle>
            Against <a href="{{ route('invoices.show', $creditNote->invoice) }}" class="link">{{ $creditNote->invoice->number }}</a> · {{ $creditNote->invoice->customer->name }}
        </x-slot:subtitle>
        <x-slot:actions>
            <a href="{{ route('credit-notes.pdf', $creditNote) }}" class="btn-secondary">Download PDF</a>
            @can('update', $creditNote)
                <a href="{{ route('credit-notes.edit', $creditNote) }}" class="btn-secondary">Edit</a>
            @endcan
            @can('delete', $creditNote)
                <x-delete-button :action="route('credit-notes.destroy', $creditNote)" confirm="Delete this credit note? Returned stock will be removed again and the invoice balance restored." />
            @endcan
        </x-slot:actions>
    </x-page-header>

    <section class="card p-6">
        <dl class="grid gap-4 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-500">Date</dt><dd class="mt-1 font-medium">{{ $creditNote->issue_date->format('d M Y') }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-slate-500">Reason</dt><dd class="mt-1">{{ $creditNote->reason ?? '—' }}</dd></div>
        </dl>
    </section>

    <section class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-th">Item</th>
                        <th class="table-th text-right">Qty</th>
                        <th class="table-th text-right">Unit price</th>
                        <th class="table-th text-right">Discount</th>
                        <th class="table-th text-right">VAT</th>
                        <th class="table-th text-right">Total</th>
                        <th class="table-th">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($creditNote->items as $item)
                        <tr>
                            <td class="table-td whitespace-normal">{{ $item->description }}</td>
                            <td class="table-td text-right tabular-nums">{{ $item->quantity }}@if ($item->free_quantity) <span class="text-emerald-600">+{{ $item->free_quantity }} free</span>@endif</td>
                            <td class="table-td text-right tabular-nums">{{ $item->unit_price->format() }}</td>
                            <td class="table-td text-right tabular-nums">{{ $item->discount_amount->isZero() ? '—' : '-'.$item->discount_amount->format() }}</td>
                            <td class="table-td text-right tabular-nums">{{ $item->tax_amount->format() }}</td>
                            <td class="table-td text-right font-medium tabular-nums">{{ $item->total->format() }}</td>
                            <td class="table-td whitespace-normal text-slate-500">{{ $item->line_note }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-6 py-4">
            <dl class="w-full max-w-xs space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="tabular-nums">{{ $creditNote->subtotal->format() }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd class="tabular-nums">{{ $creditNote->discount_total->formatAsDeduction() }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">VAT</dt><dd class="tabular-nums">{{ $creditNote->tax_total->format() }}</dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold"><dt>Total credited</dt><dd class="tabular-nums">{{ $creditNote->total->formatWithCurrency() }}</dd></div>
            </dl>
        </div>
    </section>
</x-app-layout>
