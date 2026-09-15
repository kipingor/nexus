<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a new user.
     *
     * In single-database multi-tenancy, users belong to a tenant.
     * If tenancy is initialized (tenant request), tenant_id is auto-filled
     * by the BelongsToTenant trait. On the central domain, users are created
     * without a tenant (platform admin users).
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name'      => $input['name'],
            'email'     => $input['email'],
            'password'  => Hash::make($input['password']),
            'is_active' => true,
        ]);
    }
}
