@csrf

<div
    x-data="invoiceEditor({
        rows: @js($rows),
        products: @js($products),
        customers: @js($customers->map->only(['id', 'discount_rate'])),
        customerId: @js(old('customer_id', $invoice->customer_id)),
        defaultVatRate: @js($defaultVatRate),
    })"
    class="space-y-6"
>
    <div class="card grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">
        <div class="sm:col-span-2">
            <x-input-label for="customer_id" value="Customer" />
            <select id="customer_id" name="customer_id" x-model="customerId" class="form-input mt-1" required>
                <option value="">Select a customer…</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $invoice->customer_id) === (string) $customer->id)>
                        {{ $customer->name }}{{ (float) $customer->discount_rate > 0 ? ' ('.$customer->discount_rate.'% discount)' : '' }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="issue_date" value="Issue date" />
            <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1" :value="old('issue_date', $invoice->issue_date?->toDateString())" required />
            <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="due_date" value="Due date" />
            <x-text-input id="due_date" name="due_date" type="date" class="mt-1" :value="old('due_date', $invoice->due_date?->toDateString())" />
            <p class="mt-1 text-xs text-slate-500">Leave empty for {{ config('invoicing.payment_terms_days') }}-day terms.</p>
            <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Lines</h2>
            <button type="button" class="btn-secondary" @click="addRow()">Add line</button>
        </div>

        <x-input-error :messages="$errors->get('items')" class="px-5 pt-4" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-th w-56">Product</th>
                        <th class="table-th">Description</th>
                        <th class="table-th w-28">Qty</th>
                        <th class="table-th w-28" title="Bonus units shipped free of charge">Free</th>
                        <th class="table-th w-36">Unit price</th>
                        <th class="table-th w-28">VAT %</th>
                        <th class="table-th w-32 text-right">Line total</th>
                        <th class="table-th w-10"><span class="sr-only">Remove</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(row, index) in rows" :key="index">
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                <select class="form-input" :name="`items[${index}][product_id]`" x-model="row.product_id" @change="pickProduct(row)">
                                    <option value="">Custom line</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product['id'] }}">{{ $product['name'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <input type="text" class="form-input" :name="`items[${index}][description]`" x-model="row.description" placeholder="Description">
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" min="1" step="1" class="form-input" :name="`items[${index}][quantity]`" x-model="row.quantity" required>
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" min="0" step="1" class="form-input" :name="`items[${index}][free_quantity]`" x-model="row.free_quantity">
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" min="0" step="0.01" class="form-input" :name="`items[${index}][unit_price]`" x-model="row.unit_price" required>
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" min="0" max="100" step="0.01" class="form-input" :name="`items[${index}][vat_rate]`" x-model="row.vat_rate" required>
                            </td>
                            <td class="px-4 py-3 text-right text-sm tabular-nums text-slate-700">
                                <span x-text="money(line(row).total)"></span>
                                <p class="text-xs text-slate-400" x-show="discountRate(row) > 0" x-text="`incl. ${discountRate(row)}% discount`"></p>
                            </td>
                            <td class="px-2 py-3 text-right">
                                <button type="button" class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600" @click="removeRow(index)" title="Remove line">&times;</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        @if ($errors->has('items.*'))
            <ul class="space-y-1 border-t border-slate-200 px-5 py-3 text-sm text-rose-600">
                @foreach ($errors->get('items.*') as $messages)
                    @foreach ($messages as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                @endforeach
            </ul>
        @endif

        <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-5 py-4">
            <dl class="w-full max-w-xs space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="tabular-nums" x-text="money(totals.subtotal)"></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd class="tabular-nums" x-text="(totals.discount ? '-' : '') + money(totals.discount)"></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">VAT</dt><dd class="tabular-nums" x-text="money(totals.tax)"></dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold"><dt>Total ({{ config('invoicing.currency') }})</dt><dd class="tabular-nums" x-text="money(totals.total)"></dd></div>
            </dl>
        </div>
    </div>

    <div class="card p-6">
        <x-input-label for="notes" value="Notes (printed on the invoice)" />
        <textarea id="notes" name="notes" rows="3" class="form-input mt-1">{{ old('notes', $invoice->notes) }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ $invoice->exists ? route('invoices.show', $invoice) : route('invoices.index') }}" class="btn-secondary">Cancel</a>
        <x-primary-button>{{ $invoice->exists ? 'Save changes' : 'Create invoice' }}</x-primary-button>
    </div>
</div>
