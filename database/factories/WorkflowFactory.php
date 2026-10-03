<?php

namespace Database\Factories;

use App\Enums\WorkflowTrigger;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workflow>
 */
class WorkflowFactory extends Factory
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
            'trigger' => WorkflowTrigger::TicketCreated,
            'match_type' => 'all',
            'conditions' => [],
            'actions' => [],
            'position' => 0,
            'stop_processing' => false,
            'is_active' => true,
        ];
    }
}
