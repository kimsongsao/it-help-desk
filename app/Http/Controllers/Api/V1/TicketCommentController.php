<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tickets\AddTicketComment;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketCommentRequest;
use App\Http\Resources\TicketCommentResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TicketCommentController extends Controller
{
    /**
     * List the comments on a ticket. Internal notes are only included for users allowed to see them.
     */
    public function index(Request $request, Ticket $ticket): AnonymousResourceCollection
    {
        Gate::authorize('view', $ticket);

        return TicketCommentResource::collection($ticket->comments()
            ->with(['author', 'attachments'])
            ->unless($request->user()->can('commentInternal', $ticket), fn ($query) => $query->where('is_internal', false))
            ->oldest()
            ->get());
    }

    /**
     * Add a comment to a ticket.
     */
    public function store(StoreTicketCommentRequest $request, Ticket $ticket, AddTicketComment $addComment): TicketCommentResource
    {
        $comment = $addComment->handle(
            $ticket,
            $request->user(),
            $request->validated('body'),
            $request->boolean('is_internal'),
            $request->uploadedAttachments(),
            $request->file('voice_note'),
        );

        return TicketCommentResource::make($comment->load(['author', 'attachments']));
    }
}
