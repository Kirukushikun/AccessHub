<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => fake()->unique()->numberBetween(1, 99999),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'farm' => fake()->randomElement(config('access-hub.farms')),
            'department' => fake()->randomElement(config('access-hub.departments')),
            'position' => fake()->jobTitle(),
            'roles' => [fake()->randomElement(array_keys(config('access-hub.roles')))],
            'scope' => 'all',
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    /** @param string|list<string> $roles */
    public function roles(string|array $roles): static
    {
        return $this->state(['roles' => (array) $roles]);
    }

    public function selectedScope(): static
    {
        return $this->state(['scope' => 'selected']);
    }
}
