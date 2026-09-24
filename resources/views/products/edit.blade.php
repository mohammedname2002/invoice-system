<x-app-layout :title="'Edit '.$product->name">
    <x-page-header :title="'Edit '.$product->name" :subtitle="number_format($product->stock_quantity).' in stock'">
        <x-slot:actions>
            @can('delete', $product)
                <x-delete-button :action="route('products.destroy', $product)" confirm="Delete this product? Issued invoices keep their own copy of the line." />
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('products.update', $product) }}" class="card p-6 sm:p-8">
        @method('PUT')
        @include('products._form')
    </form>

    <section class="card overflow-hidden">
        <h2 class="border-b border-slate-200 px-5 py-4 text-sm font-semibold text-slate-900">Latest stock movements</h2>
        <ul class="divide-y divide-slate-100">
            @forelse ($movements as $movement)
                <li class="flex items-center justify-between px-5 py-3 text-sm">
                    <span class="text-slate-600">{{ $movement->created_at->format('d M Y H:i') }} · {{ $movement->type->label() }}</span>
                    <span @class(['tabular-nums font-medium', 'text-emerald-600' => $movement->quantity > 0, 'text-rose-600' => $movement->quantity < 0])>
                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                    </span>
                </li>
            @empty
                <li class="px-5 py-6 text-center text-sm text-slate-500">No movements yet.</li>
            @endforelse
        </ul>
    </section>
</x-app-layout>
