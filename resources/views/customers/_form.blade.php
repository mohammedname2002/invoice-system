@csrf

<div class="grid gap-6 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input-label for="name" value="Name" />
        <x-text-input id="name" name="name" class="mt-1" :value="old('name', $customer->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email', $customer->email)" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" value="Phone" />
        <x-text-input id="phone" name="phone" class="mt-1" :value="old('phone', $customer->phone)" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="tax_number" value="Tax registration number" />
        <x-text-input id="tax_number" name="tax_number" class="mt-1" :value="old('tax_number', $customer->tax_number)" />
        <x-input-error :messages="$errors->get('tax_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="discount_rate" value="Trade discount (%)" />
        <x-text-input id="discount_rate" name="discount_rate" type="number" step="0.01" min="0" max="100" class="mt-1" :value="old('discount_rate', $customer->discount_rate)" required />
        <p class="mt-1 text-xs text-slate-500">Applied before VAT to every product that allows customer discounts.</p>
        <x-input-error :messages="$errors->get('discount_rate')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="address" value="Address" />
        <textarea id="address" name="address" rows="3" class="form-input mt-1">{{ old('address', $customer->address) }}</textarea>
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>
</div>

<div class="mt-8 flex items-center justify-end gap-3">
    <a href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}" class="btn-secondary">Cancel</a>
    <x-primary-button>{{ $customer->exists ? 'Save changes' : 'Create customer' }}</x-primary-button>
</div>
