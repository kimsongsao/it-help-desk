<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tickets\EscalateTicket;
use App\Enums\EscalationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\EscalateTicketRequest;
use App\Http\Resources\TicketEscalationResource;
use App\Models\Ticket;
use App\Models\User;

class TicketEscalationController extends Controller
{
    /**
     * Escalate a ticket to the next level.
     */
    public function store(EscalateTicketRequest $request, Ticket $ticket, EscalateTicket $escalateTicket): TicketEscalationResource
    {
        $escalation = $escalateTicket->handle(
            $ticket,
            $request->validated('reason'),
            EscalationType::Manual,
            $request->user(),
            User::find($request->validated('escalate_to_id')),
        );

        return TicketEscalationResource::make($escalation->load(['escalatedBy', 'escalatedTo']));
    }
}
