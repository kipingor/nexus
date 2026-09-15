<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'website'  => ['nullable', 'url', 'max:255'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'email'    => ['nullable', 'email', 'max:255'],
            'address'  => ['nullable', 'string', 'max:500'],
            'notes'    => ['nullable', 'string', 'max:5000'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
