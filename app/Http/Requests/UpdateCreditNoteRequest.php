<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreditNoteRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'issue_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
