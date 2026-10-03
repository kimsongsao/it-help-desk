<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Models\SlaPolicy;
use App\Models\User;
use Illuminate\Database\Seeder;

class SlaPolicySeeder extends Seeder
{
    /**
     * Seed one SLA policy per priority.
     */
    public function run(): void
    {
        $supervisor = User::whereRelation('role', 'slug', 'supervisor')->first();

        $policies = [
            [TicketPriority::Critical, 15, 4 * 60, false],
            [TicketPriority::High, 60, 8 * 60, false],
            [TicketPriority::Medium, 4 * 60, 3 * 8 * 60, true],
            [TicketPriority::Low, 8 * 60, 5 * 8 * 60, true],
        ];

        foreach ($policies as [$priority, $firstResponse, $resolution, $businessHours]) {
            SlaPolicy::updateOrCreate(['name' => "{$priority->label()} priority"], [
                'description' => $businessHours ? 'Counted in business hours (Mon–Fri, 09:00–17:00).' : 'Counted around the clock.',
                'priority' => $priority,
                'first_response_minutes' => $firstResponse,
                'resolution_minutes' => $resolution,
                'business_hours_only' => $businessHours,
                'business_hours_start' => '09:00',
                'business_hours_end' => '17:00',
                'business_days' => [1, 2, 3, 4, 5],
                'escalate_on_breach' => true,
                'escalate_to_id' => $supervisor?->id,
                'is_active' => true,
            ]);
        }
    }
}
