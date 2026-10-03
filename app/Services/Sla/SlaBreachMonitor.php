<?php

namespace App\Services\Sla;

use App\Actions\Tickets\EscalateTicket;
use App\Enums\EscalationType;
use App\Enums\TicketActivityType;
use App\Enums\WorkflowTrigger;
use App\Models\Ticket;
use App\Services\Workflows\WorkflowEngine;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;

class SlaBreachMonitor
{
    public function __construct(
        private EscalateTicket $escalateTicket,
        private WorkflowEngine $workflows,
        private Tenancy $tenancy,
    ) {}

    /**
     * Flag every running ticket that has newly missed an SLA target and escalate it.
     *
     * Runs across every organization; each ticket is processed as its own organization.
     *
     * @return int The number of tickets that breached.
     */
    public function run(): int
    {
        $breached = 0;

        Ticket::query()
            ->active()
            ->whereNull('sla_paused_at')
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->whereNull('first_response_breached_at')
                    ->whereNull('first_responded_at')
                    ->where('first_response_due_at', '<', now()))
                ->orWhere(fn (Builder $query) => $query
                    ->whereNull('resolution_breached_at')
                    ->where('resolution_due_at', '<', now())))
            ->whereHas('organization', fn (Builder $query) => $query->where('is_active', true))
            ->with(['organization', 'slaPolicy.escalateTo', 'requester', 'assignee'])
            ->lazyById()
            ->each(function (Ticket $ticket) use (&$breached): void {
                if ($this->tenancy->runAs($ticket->organization, fn (): bool => $this->recordBreach($ticket))) {
                    $breached++;
                }
            });

        return $breached;
    }

    /**
     * Mark the missed targets on the ticket, then escalate and run the breach workflows.
     */
    public function recordBreach(Ticket $ticket): bool
    {
        $missed = [];

        if ($ticket->first_response_breached_at === null && $ticket->first_responded_at === null && $ticket->first_response_due_at?->isPast()) {
            $ticket->first_response_breached_at = now();
            $missed[] = 'first response';
        }

        if ($ticket->resolution_breached_at === null && $ticket->resolution_due_at?->isPast()) {
            $ticket->resolution_breached_at = now();
            $missed[] = 'resolution';
        }

        if ($missed === []) {
            return false;
        }

        $ticket->save();

        $targets = implode(' and ', $missed);
        $ticket->recordActivity(TicketActivityType::SlaBreached, ':targets SLA target missed', replace: ['targets' => ucfirst($targets)]);

        if ($ticket->slaPolicy?->escalate_on_breach) {
            $this->escalateTicket->handle(
                $ticket,
                "The {$targets} SLA target of policy \"{$ticket->slaPolicy->name}\" was missed.",
                EscalationType::Automatic,
                escalateTo: $ticket->slaPolicy->escalateTo,
            );
        }

        $this->workflows->run(WorkflowTrigger::SlaBreached, $ticket);

        return true;
    }
}
