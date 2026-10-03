<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tickets\CreateTicket;
use App\Actions\Tickets\UpdateTicket;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListTicketsRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    /**
     * List the tickets visible to the current user.
     */
    public function index(ListTicketsRequest $request): AnonymousResourceCollection
    {
        $tickets = Ticket::visibleTo($request->user())
            ->filter($request->validated(), $request->user())
            ->with(['requester', 'assignee', 'category', 'team'])
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return TicketResource::collection($tickets);
    }

    /**
     * Submit a new ticket.
     */
    public function store(StoreTicketRequest $request, CreateTicket $createTicket): TicketResource
    {
        $ticket = $createTicket->handle($request->user(), $request->ticketAttributes(), $request->uploadedAttachments(), $request->file('voice_note'));

        return TicketResource::make($ticket->load(['requester', 'assignee', 'category', 'team', 'slaPolicy', 'ticketAttachments']));
    }

    /**
     * Show a ticket.
     */
    public function show(Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        return TicketResource::make($ticket->load(['requester', 'assignee', 'category', 'team', 'slaPolicy', 'ticketAttachments', 'escalations.escalatedBy', 'escalations.escalatedTo']));
    }

    /**
     * Update a ticket's status, priority, category or assignee.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket, UpdateTicket $updateTicket): TicketResource
    {
        $updateTicket->handle($ticket, $request->validated(), $request->user());

        return TicketResource::make($ticket->fresh(['requester', 'assignee', 'category', 'team', 'slaPolicy']));
    }

    /**
     * Delete a ticket.
     */
    public function destroy(Ticket $ticket): Response
    {
        Gate::authorize('delete', $ticket);

        $ticket->delete();

        return response()->noContent();
    }
}
