<?php

namespace App\Actions\Tickets;

use App\Enums\Feature;
use App\Enums\TicketActivityType;
use App\Enums\WorkflowTrigger;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Sla\SlaManager;
use App\Services\TicketUpdater;
use App\Services\Workflows\WorkflowEngine;
use Illuminate\Http\UploadedFile;

class CreateTicket
{
    public function __construct(
        private SlaManager $sla,
        private StoreTicketAttachments $storeAttachments,
        private TicketUpdater $updater,
        private WorkflowEngine $workflows,
    ) {}

    /**
     * Open a new ticket for the requester, store its files, start its SLA clock and run the creation workflows.
     *
     * @param  array{subject: string, description?: ?string, priority: string, category_id: int}  $attributes
     * @param  list<UploadedFile>  $attachments
     */
    public function handle(User $requester, array $attributes, array $attachments = [], ?UploadedFile $voiceNote = null): Ticket
    {
        $ticket = new Ticket([...$attributes, 'description' => $attributes['description'] ?? '']);
        $ticket->requester()->associate($requester);
        $ticket->organization_id = $requester->organization_id;
        $ticket->created_at = now();

        $this->sla->applyPolicy($ticket);
        $ticket->save();

        $this->storeAttachments->handle($ticket, $requester, $attachments, $voiceNote);

        $ticket->recordActivity(TicketActivityType::Created, 'Ticket submitted', $requester);

        $this->routeToDefaultTeam($ticket);

        $this->workflows->run(WorkflowTrigger::TicketCreated, $ticket);

        return $ticket;
    }

    /**
     * Send the ticket to the team its category routes to, when the organization's plan includes teams.
     */
    private function routeToDefaultTeam(Ticket $ticket): void
    {
        $teamId = $ticket->category?->default_team_id;

        if ($teamId !== null && $ticket->organization->hasFeature(Feature::Teams)) {
            $this->updater->apply($ticket, ['team_id' => $teamId], null);
        }
    }
}
