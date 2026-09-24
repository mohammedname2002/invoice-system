<div class="card overflow-hidden">
    <div class="border-b border-slate-200 p-4">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search by name or SKU…" class="form-input sm:max-w-sm" aria-label="Search products">
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="table-th">Product</th>
                    <th class="table-th">SKU</th>
                    <th class="table-th text-right">Unit price</th>
                    <th class="table-th text-right">VAT</th>
                    <th class="table-th">Customer discount</th>
                    <th class="table-th text-right">In stock</th>
                    <th class="table-th"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    <tr wire:key="product-{{ $product->id }}" class="hover:bg-slate-50">
                        <td class="table-td font-medium text-slate-900">{{ $product->name }}</td>
                        <td class="table-td font-mono text-xs">{{ $product->sku ?? '—' }}</td>
                        <td class="table-td text-right tabular-nums">{{ $product->unit_price->format() }}</td>
                        <td class="table-td text-right tabular-nums">{{ $product->vat_rate }}%</td>
                        <td class="table-td">{{ $product->apply_customer_discount ? 'Applies' : 'Excluded' }}</td>
                        <td @class(['table-td text-right tabular-nums', 'text-rose-600 font-medium' => $product->stock_quantity < 0])>{{ number_format($product->stock_quantity) }}</td>
                        <td class="table-td text-right">
                            @can('update', $product)
                                <a href="{{ route('products.edit', $product) }}" class="link">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($products->hasPages())
        <div class="border-t border-slate-200 px-4 py-3">{{ $products->links() }}</div>
    @endif
</div>
