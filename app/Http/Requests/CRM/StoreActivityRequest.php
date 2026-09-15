<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type'     => ['required', 'in:call,email,meeting,note,task'],
            'subject'  => ['required', 'string', 'max:255'],
            'body'     => ['nullable', 'string', 'max:10000'],
            'due_at'   => ['nullable', 'date'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
