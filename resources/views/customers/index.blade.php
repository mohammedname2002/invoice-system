<x-app-layout title="Customers">
    <x-page-header title="Customers" subtitle="The companies you invoice.">
        <x-slot:actions>
            @can('create', App\Models\Customer::class)
                <a href="{{ route('customers.create') }}" class="btn-primary">New customer</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <livewire:customer-table />
</x-app-layout>
