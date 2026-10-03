<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\AddTicketComment;
use App\Http\Requests\StoreTicketCommentRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

class TicketCommentController extends Controller
{
    /**
     * Add a reply to the ticket.
     */
    public function store(StoreTicketCommentRequest $request, Ticket $ticket, AddTicketComment $addComment): RedirectResponse
    {
        $addComment->handle(
            $ticket,
            $request->user(),
            $request->validated('body'),
            $request->boolean('is_internal'),
            $request->uploadedAttachments(),
            $request->file('voice_note'),
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('status', __('Reply added.'));
    }
}
