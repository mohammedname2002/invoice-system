<x-app-layout title="New credit note">
    <x-page-header title="New credit note" subtitle="Credit returned or disputed units. Returned units go back into stock." />

    <form method="GET" action="{{ route('credit-notes.create') }}" class="card flex flex-col gap-3 p-6 sm:flex-row sm:items-end">
        <div class="flex-1">
            <x-input-label for="invoice" value="Invoice" />
            <select id="invoice" name="invoice" class="form-input mt-1" onchange="this.form.submit()">
                <option value="">Select an invoice…</option>
                @foreach ($invoices as $option)
                    <option value="{{ $option->id }}" @selected($invoice?->id === $option->id)>
                        {{ $option->number }} · {{ $option->customer->name }} · {{ $option->total->formatWithCurrency() }}
                    </option>
                @endforeach
            </select>
        </div>
        <noscript><button class="btn-secondary">Load lines</button></noscript>
    </form>

    @if ($invoice)
        <form method="POST" action="{{ route('credit-notes.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

            <div class="card grid gap-6 p-6 sm:grid-cols-3">
                <div>
                    <x-input-label for="issue_date" value="Credit note date" />
                    <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1" :value="old('issue_date', now()->toDateString())" required />
                    <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="reason" value="Reason" />
                    <x-text-input id="reason" name="reason" class="mt-1" :value="old('reason')" placeholder="e.g. damaged in transit" />
                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                </div>
            </div>

            <div class="card overflow-hidden">
                <x-input-error :messages="$errors->get('items')" class="px-5 pt-4" />
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="table-th">Item</th>
                                <th class="table-th text-right">Invoiced</th>
                                <th class="table-th text-right">Already credited</th>
                                <th class="table-th text-right">Unit price</th>
                                <th class="table-th w-28">Credit qty</th>
                                <th class="table-th w-28">Return free</th>
                                <th class="table-th">Line note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($items as $i => $item)
                                <tr class="align-top">
                                    <td class="table-td whitespace-normal">
                                        {{ $item->description }}
                                        <input type="hidden" name="items[{{ $i }}][invoice_item_id]" value="{{ $item->id }}">
                                    </td>
                                    <td class="table-td text-right tabular-nums">{{ $item->quantity }}@if ($item->free_quantity) +{{ $item->free_quantity }}@endif</td>
                                    <td class="table-td text-right tabular-nums">{{ (int) $item->credited_quantity }}@if ($item->free_quantity) +{{ (int) $item->credited_free_quantity }}@endif</td>
                                    <td class="table-td text-right tabular-nums">{{ $item->unit_price->format() }}</td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="items[{{ $i }}][quantity]" min="0" max="{{ $item->available_quantity }}" value="{{ old("items.$i.quantity", 0) }}" class="form-input" @disabled($item->available_quantity === 0)>
                                        <x-input-error :messages="$errors->get('items.'.$i.'.quantity')" class="mt-1" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="items[{{ $i }}][free_quantity]" min="0" max="{{ $item->available_free_quantity }}" value="{{ old("items.$i.free_quantity", 0) }}" class="form-input" @disabled($item->available_free_quantity === 0)>
                                        <x-input-error :messages="$errors->get('items.'.$i.'.free_quantity')" class="mt-1" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" name="items[{{ $i }}][line_note]" value="{{ old("items.$i.line_note") }}" class="form-input" maxlength="500">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="border-t border-slate-200 bg-slate-50 px-5 py-3 text-xs text-slate-500">
                    Credited lines use the price, discount and VAT rate from the original invoice. Totals are calculated when you save.
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('invoices.show', $invoice) }}" class="btn-secondary">Cancel</a>
                <x-primary-button>Issue credit note</x-primary-button>
            </div>
        </form>
    @endif
</x-app-layout>
