<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by policy/gate at the controller level
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9][a-z0-9\-]*[a-z0-9]$/',
                Rule::unique('tenants', 'slug'),
            ],
            'plan' => ['nullable', 'string', Rule::in(['starter', 'growth', 'enterprise'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'subdomain.regex' => 'Subdomain may only contain lowercase letters, numbers, and hyphens.',
            'subdomain.unique' => 'That subdomain is already taken.',
        ];
    }
}
