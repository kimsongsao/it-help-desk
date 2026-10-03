<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraphs(2, true),
            'status' => TicketStatus::Open,
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'category_id' => Category::factory(),
            'requester_id' => User::factory(),
            'assignee_id' => null,
        ];
    }

    /**
     * Indicate that the ticket is assigned to a support agent.
     */
    public function assigned(?User $agent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'assignee_id' => $agent ?? User::factory()->agent(),
        ]);
    }

    /**
     * Indicate that the ticket has the given status.
     */
    public function status(TicketStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
