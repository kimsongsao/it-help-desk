<?php

namespace Database\Factories;

use App\Enums\Plan;
use App\Models\Organization;
use App\Support\DefaultRoles;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'plan' => Plan::Enterprise,
            'is_active' => true,
            'allowed_email_domains' => [],
            'allow_self_registration' => false,
            'default_locale' => 'en',
        ];
    }

    /**
     * Give every organization its built-in roles.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (Organization $organization) => DefaultRoles::createFor($organization));
    }

    /**
     * Put the organization on the given plan.
     */
    public function plan(Plan $plan): static
    {
        return $this->state(fn (array $attributes) => ['plan' => $plan]);
    }

    /**
     * Indicate that the organization has been suspended.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
