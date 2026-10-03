<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\EscalateTicket;
use App\Enums\EscalationType;
use App\Http\Requests\EscalateTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class TicketEscalationController extends Controller
{
    /**
     * Escalate the ticket to the next level.
     */
    public function store(EscalateTicketRequest $request, Ticket $ticket, EscalateTicket $escalateTicket): RedirectResponse
    {
        $escalation = $escalateTicket->handle(
            $ticket,
            $request->validated('reason'),
            EscalationType::Manual,
            $request->user(),
            User::find($request->validated('escalate_to_id')),
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('status', __('Ticket escalated to level :level.', ['level' => $escalation->level]));
    }
}
