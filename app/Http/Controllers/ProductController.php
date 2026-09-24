<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(): View
    {
        return view('products.index');
    }

    public function create(): View
    {
        $product = new Product([
            'vat_rate' => config('invoicing.default_vat_rate'),
            'apply_customer_discount' => true,
        ]);
        $product->unit_price = Money::zero();

        return view('products.create', ['product' => $product]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated());

        return to_route('products.index')->with('status', "Product {$product->name} created.");
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'movements' => $product->inventoryMovements()->latest('id')->limit(10)->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->validated());

        return to_route('products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->delete($product);

        return to_route('products.index')->with('status', 'Product deleted.');
    }
}
