<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['nullable', 'string', 'max:100'],
            'email'      => ['nullable', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'job_title'  => ['nullable', 'string', 'max:150'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'stage'      => ['required', 'in:lead,prospect,customer,churned'],
            'source'     => ['required', 'in:website,referral,social,email,phone,event,other'],
            'notes'      => ['nullable', 'string', 'max:5000'],
            'owner_id'   => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
