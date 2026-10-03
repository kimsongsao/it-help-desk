<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketAttachment>
 */
class TicketAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'disk' => 'local',
            'path' => 'attachments/'.fake()->uuid().'.txt',
            'original_name' => fake()->word().'.txt',
            'mime_type' => 'text/plain',
            'size' => fake()->numberBetween(100, 100_000),
            'is_voice_note' => false,
        ];
    }
}
