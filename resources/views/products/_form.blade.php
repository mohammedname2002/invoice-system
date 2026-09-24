@csrf

<div class="grid gap-6 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input-label for="name" value="Name" />
        <x-text-input id="name" name="name" class="mt-1" :value="old('name', $product->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sku" value="SKU" />
        <x-text-input id="sku" name="sku" class="mt-1 font-mono" :value="old('sku', $product->sku)" />
        <x-input-error :messages="$errors->get('sku')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="unit_price" :value="'Unit price ('.config('invoicing.currency').', excl. VAT)'" />
        <x-text-input id="unit_price" name="unit_price" type="number" step="0.01" min="0" class="mt-1" :value="old('unit_price', $product->unit_price->toDecimal())" required />
        <x-input-error :messages="$errors->get('unit_price')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="vat_rate" value="VAT rate (%)" />
        <x-text-input id="vat_rate" name="vat_rate" type="number" step="0.01" min="0" max="100" class="mt-1" :value="old('vat_rate', $product->vat_rate)" required />
        <x-input-error :messages="$errors->get('vat_rate')" class="mt-2" />
    </div>

    @unless ($product->exists)
        <div>
            <x-input-label for="stock_quantity" value="Opening stock" />
            <x-text-input id="stock_quantity" name="stock_quantity" type="number" step="1" min="0" class="mt-1" :value="old('stock_quantity', 0)" />
            <p class="mt-1 text-xs text-slate-500">After this, stock moves only through invoices and credit notes.</p>
            <x-input-error :messages="$errors->get('stock_quantity')" class="mt-2" />
        </div>
    @endunless

    <div class="sm:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="apply_customer_discount" value="0">
            <input type="checkbox" name="apply_customer_discount" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(old('apply_customer_discount', $product->apply_customer_discount))>
            Apply the customer's trade discount to this product
        </label>
    </div>
</div>

<div class="mt-8 flex items-center justify-end gap-3">
    <a href="{{ route('products.index') }}" class="btn-secondary">Cancel</a>
    <x-primary-button>{{ $product->exists ? 'Save changes' : 'Create product' }}</x-primary-button>
</div>
