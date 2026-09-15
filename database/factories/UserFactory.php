<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'tenant_id'         => null, // Central (platform) user by default; override in tests.
            'name'              => $this->faker->name(),
            'email'             => $this->faker->unique()->safeEmail(),
            'phone'             => $this->faker->optional()->phoneNumber(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'is_active'         => true,
            'remember_token'    => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function forTenant(Tenant|string $tenant): static
    {
        return $this->state(['tenant_id' => $tenant instanceof Tenant ? $tenant->id : $tenant]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
