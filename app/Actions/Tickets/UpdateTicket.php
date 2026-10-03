<?php

namespace App\Actions\Tickets;

use App\Enums\WorkflowTrigger;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketUpdater;
use App\Services\Workflows\WorkflowEngine;

class UpdateTicket
{
    public function __construct(
        private TicketUpdater $updater,
        private WorkflowEngine $workflows,
    ) {}

    /**
     * Apply triage changes made by a user and run the update workflows.
     *
     * @param  array{status?: string, priority?: string, category_id?: int, assignee_id?: ?int}  $attributes
     */
    public function handle(Ticket $ticket, array $attributes, User $actor): Ticket
    {
        $changed = $this->updater->apply($ticket, $attributes, $actor);

        if ($changed !== []) {
            $this->workflows->run(WorkflowTrigger::TicketUpdated, $ticket, $changed);
        }

        return $ticket;
    }
}
