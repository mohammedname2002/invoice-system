<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.description' => ['nullable', 'required_without:items.*.product_id', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.free_quantity' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'items.*.unit_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:10000000'],
            'items.*.vat_rate' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'items.*.product_id' => 'product',
            'items.*.description' => 'description',
            'items.*.quantity' => 'quantity',
            'items.*.free_quantity' => 'free quantity',
            'items.*.unit_price' => 'unit price',
            'items.*.vat_rate' => 'VAT rate',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one line to the invoice.',
            'items.*.description.required_without' => 'Pick a product or enter a description.',
        ];
    }

    /**
     * Ignore completely blank rows left over in the line editor.
     */
    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('items'))) {
            return;
        }

        $this->merge([
            'items' => collect($this->input('items'))
                ->reject(fn ($row) => is_array($row)
                    && blank($row['product_id'] ?? null)
                    && blank($row['description'] ?? null)
                    && blank($row['unit_price'] ?? null))
                ->values()
                ->all(),
        ]);
    }
}
