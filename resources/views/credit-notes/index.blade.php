<x-app-layout title="Credit notes">
    <x-page-header title="Credit notes" subtitle="Returns and corrections against issued invoices.">
        <x-slot:actions>
            @can('create', App\Models\CreditNote::class)
                <a href="{{ route('credit-notes.create') }}" class="btn-primary">New credit note</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <livewire:credit-note-table />
</x-app-layout>
