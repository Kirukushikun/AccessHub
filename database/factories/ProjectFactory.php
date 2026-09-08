<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'key' => Str::slug($name),
            'name' => Str::title($name),
            'acceptance' => 'open',
            'active' => true,
        ];
    }

    public function explicit(): static
    {
        return $this->state(['acceptance' => 'explicit']);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
