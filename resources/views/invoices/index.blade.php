<x-app-layout title="Invoices">
    <x-page-header title="Invoices" subtitle="Search, filter and open invoices.">
        <x-slot:actions>
            @can('create', App\Models\Invoice::class)
                <a href="{{ route('invoices.create') }}" class="btn-primary">New invoice</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <livewire:invoice-table />
</x-app-layout>
