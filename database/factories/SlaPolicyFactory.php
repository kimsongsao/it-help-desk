<?php

namespace Database\Factories;

use App\Models\SlaPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlaPolicy>
 */
class SlaPolicyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'priority' => null,
            'category_id' => null,
            'first_response_minutes' => 60,
            'resolution_minutes' => 480,
            'business_hours_only' => false,
            'business_hours_start' => '09:00',
            'business_hours_end' => '17:00',
            'business_days' => [1, 2, 3, 4, 5],
            'escalate_on_breach' => true,
            'escalate_to_id' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the policy only counts business hours.
     */
    public function businessHours(string $start = '09:00', string $end = '17:00', array $days = [1, 2, 3, 4, 5]): static
    {
        return $this->state(fn (array $attributes) => [
            'business_hours_only' => true,
            'business_hours_start' => $start,
            'business_hours_end' => $end,
            'business_days' => $days,
        ]);
    }
}
