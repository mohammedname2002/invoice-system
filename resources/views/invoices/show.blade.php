<x-app-layout :title="$invoice->number">
    @php($locked = $invoice->payments->isNotEmpty() || $invoice->creditNotes->isNotEmpty())

    <x-page-header :title="$invoice->number">
        <x-slot:subtitle>{{ $invoice->customer->name }}</x-slot:subtitle>
        <x-slot:actions>
            <x-status-badge :status="$invoice->status" class="mr-2" />
            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn-secondary">Download PDF</a>
            @can('create', App\Models\CreditNote::class)
                <a href="{{ route('credit-notes.create', ['invoice' => $invoice->id]) }}" class="btn-secondary">Issue credit note</a>
            @endcan
            @if (! $locked)
                @can('update', $invoice)
                    <a href="{{ route('invoices.edit', $invoice) }}" class="btn-secondary">Edit</a>
                @endcan
                @can('delete', $invoice)
                    <x-delete-button :action="route('invoices.destroy', $invoice)" confirm="Delete this invoice? Stock will be returned." />
                @endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card p-6">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="text-sm">
                        <p class="font-medium text-slate-500">Billed to</p>
                        <p class="mt-1 font-semibold text-slate-900"><a class="link" href="{{ route('customers.show', $invoice->customer) }}">{{ $invoice->customer->name }}</a></p>
                        @if ($invoice->customer->tax_number)<p class="text-slate-600">TRN {{ $invoice->customer->tax_number }}</p>@endif
                        @if ($invoice->customer->address)<p class="text-slate-600">{{ $invoice->customer->address }}</p>@endif
                    </div>
                    <dl class="grid grid-cols-2 gap-2 text-sm sm:text-right">
                        <dt class="text-slate-500">Issued</dt><dd>{{ $invoice->issue_date->format('d M Y') }}</dd>
                        <dt class="text-slate-500">Due</dt><dd @class(['font-medium text-rose-600' => $invoice->status === App\Enums\InvoiceStatus::Overdue])>{{ $invoice->due_date->format('d M Y') }}</dd>
                        <dt class="text-slate-500">Discount</dt><dd>{{ $invoice->discount_rate }}%</dd>
                    </dl>
                </div>
            </section>

            <section class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="table-th">#</th>
                                <th class="table-th">Item</th>
                                <th class="table-th text-right">Qty</th>
                                <th class="table-th text-right">Unit price</th>
                                <th class="table-th text-right">Discount</th>
                                <th class="table-th text-right">VAT</th>
                                <th class="table-th text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="table-td text-slate-400">{{ $loop->iteration }}</td>
                                    <td class="table-td whitespace-normal">{{ $item->description }}</td>
                                    <td class="table-td text-right tabular-nums">{{ $item->quantity }}@if ($item->free_quantity) <span class="text-emerald-600">+{{ $item->free_quantity }} free</span>@endif</td>
                                    <td class="table-td text-right tabular-nums">{{ $item->unit_price->format() }}</td>
                                    <td class="table-td text-right tabular-nums">{{ $item->discount_amount->isZero() ? '—' : '-'.$item->discount_amount->format() }}</td>
                                    <td class="table-td text-right tabular-nums">{{ $item->tax_amount->format() }} <span class="text-xs text-slate-400">({{ $item->vat_rate }}%)</span></td>
                                    <td class="table-td text-right font-medium tabular-nums">{{ $item->total->format() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <dl class="w-full max-w-xs space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="tabular-nums">{{ $invoice->subtotal->format() }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd class="tabular-nums">{{ $invoice->discount_total->formatAsDeduction() }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">VAT</dt><dd class="tabular-nums">{{ $invoice->tax_total->format() }}</dd></div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold"><dt>Total</dt><dd class="tabular-nums">{{ $invoice->total->formatWithCurrency() }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Credited</dt><dd class="tabular-nums">{{ $invoice->amount_credited->formatAsDeduction() }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd class="tabular-nums">{{ $invoice->amount_paid->formatAsDeduction() }}</dd></div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold"><dt>Balance due</dt><dd class="tabular-nums">{{ $invoice->balance()->formatWithCurrency() }}</dd></div>
                    </dl>
                </div>
            </section>

            @if ($invoice->notes)
                <section class="card p-6 text-sm text-slate-700">
                    <p class="font-medium text-slate-500">Notes</p>
                    <p class="mt-1 whitespace-pre-line">{{ $invoice->notes }}</p>
                </section>
            @endif
        </div>

        <div class="space-y-6">
            @can('recordPayment', $invoice)
                @if ($invoice->balance()->isPositive())
                    <section class="card p-6">
                        <h2 class="text-sm font-semibold text-slate-900">Record a payment</h2>
                        <form method="POST" action="{{ route('payments.store', $invoice) }}" class="mt-4 space-y-4">
                            @csrf
                            <div>
                                <x-input-label for="amount" :value="'Amount ('.config('invoicing.currency').')'" />
                                <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1" :value="old('amount', $invoice->balance()->toDecimal())" required />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <x-input-label for="paid_at" value="Date" />
                                    <x-text-input id="paid_at" name="paid_at" type="date" class="mt-1" :value="old('paid_at', now()->toDateString())" required />
                                    <x-input-error :messages="$errors->get('paid_at')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="method" value="Method" />
                                    <select id="method" name="method" class="form-input mt-1">
                                        @foreach ($paymentMethods as $method)
                                            <option value="{{ $method->value }}" @selected(old('method') === $method->value)>{{ $method->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('method')" class="mt-2" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="reference" value="Reference" />
                                <x-text-input id="reference" name="reference" class="mt-1" :value="old('reference')" placeholder="Transfer ID, cheque no…" />
                                <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                            </div>
                            <x-primary-button class="w-full">Record payment</x-primary-button>
                        </form>
                    </section>
                @endif
            @endcan

            <section class="card overflow-hidden">
                <h2 class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">Payments</h2>
                <ul class="divide-y divide-slate-100">
                    @forelse ($invoice->payments as $payment)
                        <li class="flex items-start justify-between gap-3 px-5 py-3 text-sm">
                            <div>
                                <p class="font-medium tabular-nums text-slate-900">{{ $payment->amount->formatWithCurrency() }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $payment->paid_at->format('d M Y') }} · {{ $payment->method->label() }}{{ $payment->reference ? ' · '.$payment->reference : '' }}
                                    @if ($payment->recorder) · by {{ $payment->recorder->name }} @endif
                                </p>
                            </div>
                            @can('delete', $payment)
                                <form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('Remove this payment?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-rose-600 hover:text-rose-700">Remove</button>
                                </form>
                            @endcan
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-slate-500">No payments yet.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card overflow-hidden">
                <h2 class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">Credit notes</h2>
                <ul class="divide-y divide-slate-100">
                    @forelse ($invoice->creditNotes as $creditNote)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <a href="{{ route('credit-notes.show', $creditNote) }}" class="link">{{ $creditNote->number }}</a>
                            <span class="tabular-nums text-slate-700">{{ $creditNote->total->formatAsDeduction() }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-slate-500">No credit notes.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-app-layout>
