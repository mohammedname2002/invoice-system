<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditNoteRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'issue_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.invoice_item_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.free_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.line_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
