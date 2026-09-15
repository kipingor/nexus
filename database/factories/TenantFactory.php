<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $slug = $this->faker->unique()->slug(2);

        return [
            'id'            => $slug,
            'name'          => $this->faker->company(),
            'slug'          => $slug,
            'status'        => $this->faker->randomElement(['trial', 'active', 'suspended']),
            'plan'          => $this->faker->randomElement(['starter', 'growth', 'enterprise']),
            'trial_ends_at' => now()->addDays(14),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function suspended(): static
    {
        return $this->state(['status' => 'suspended']);
    }

    public function trial(): static
    {
        return $this->state(['status' => 'trial']);
    }
}
