<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'unit_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:10000000'],
            'vat_rate' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'apply_customer_discount' => ['boolean'],
            // Opening stock only; afterwards stock moves through invoices and credit notes.
            'stock_quantity' => [Rule::excludeIf($this->route('product') !== null), 'nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['apply_customer_discount' => $this->boolean('apply_customer_discount')]);
    }
}
