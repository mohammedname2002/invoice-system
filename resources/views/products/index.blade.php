<x-app-layout title="Products">
    <x-page-header title="Products" subtitle="Catalogue items with price, VAT rate and stock.">
        <x-slot:actions>
            @can('create', App\Models\Product::class)
                <a href="{{ route('products.create') }}" class="btn-primary">New product</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <livewire:product-table />
</x-app-layout>
