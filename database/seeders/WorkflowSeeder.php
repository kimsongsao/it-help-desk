<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\WorkflowActionType;
use App\Enums\WorkflowField;
use App\Enums\WorkflowOperator;
use App\Enums\WorkflowTrigger;
use App\Models\Category;
use App\Models\Workflow;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    /**
     * Seed example automation rules.
     */
    public function run(): void
    {
        $network = Category::firstWhere('name', 'Network');

        Workflow::updateOrCreate(['name' => 'Outages are critical'], [
            'description' => 'Raise tickets that mention an outage to critical priority.',
            'trigger' => WorkflowTrigger::TicketCreated,
            'match_type' => 'any',
            'conditions' => [
                ['field' => WorkflowField::Subject->value, 'operator' => WorkflowOperator::Contains->value, 'value' => 'outage'],
                ['field' => WorkflowField::Subject->value, 'operator' => WorkflowOperator::Contains->value, 'value' => 'down'],
            ],
            'actions' => [
                ['type' => WorkflowActionType::SetPriority->value, 'value' => TicketPriority::Critical->value],
                ['type' => WorkflowActionType::AddInternalNote->value, 'value' => 'Possible outage – check the monitoring dashboard first.'],
            ],
            'position' => 10,
        ]);

        if ($network) {
            Workflow::updateOrCreate(['name' => 'Route network tickets'], [
                'description' => 'Assign new network tickets to the least busy agent.',
                'trigger' => WorkflowTrigger::TicketCreated,
                'match_type' => 'all',
                'conditions' => [
                    ['field' => WorkflowField::Category->value, 'operator' => WorkflowOperator::Equals->value, 'value' => (string) $network->id],
                ],
                'actions' => [
                    ['type' => WorkflowActionType::AssignLeastBusy->value, 'value' => null],
                ],
                'position' => 20,
            ]);
        }

        Workflow::updateOrCreate(['name' => 'Note SLA breaches'], [
            'description' => 'Leave a note for the team whenever an SLA is missed.',
            'trigger' => WorkflowTrigger::SlaBreached,
            'match_type' => 'all',
            'conditions' => [],
            'actions' => [
                ['type' => WorkflowActionType::AddInternalNote->value, 'value' => 'SLA breached – please prioritise this ticket.'],
            ],
            'position' => 10,
        ]);
    }
}
