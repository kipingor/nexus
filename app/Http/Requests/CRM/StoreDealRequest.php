<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class StoreDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:255'],
            'value'               => ['required', 'numeric', 'min:0'],
            'currency'            => ['nullable', 'string', 'size:3'],
            'stage'               => ['required', 'in:new,qualified,proposal,negotiation,won,lost'],
            'expected_close_date' => ['nullable', 'date'],
            'notes'               => ['nullable', 'string', 'max:5000'],
            'contact_id'          => ['nullable', 'integer', 'exists:contacts,id'],
            'company_id'          => ['nullable', 'integer', 'exists:companies,id'],
            'owner_id'            => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
