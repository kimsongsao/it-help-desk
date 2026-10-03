<?php

namespace App\Actions\Tickets;

use App\Enums\TicketActivityType;
use App\Enums\TicketStatus;
use App\Enums\WorkflowTrigger;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\TicketReplied;
use App\Services\Sla\SlaManager;
use App\Services\TicketUpdater;
use App\Services\Workflows\WorkflowEngine;
use Illuminate\Http\UploadedFile;

class AddTicketComment
{
    public function __construct(
        private SlaManager $sla,
        private StoreTicketAttachments $storeAttachments,
        private TicketUpdater $updater,
        private WorkflowEngine $workflows,
    ) {}

    /**
     * Add a reply or internal note to the ticket.
     *
     * A public staff reply counts as the first response; a requester reply reopens a finished ticket.
     *
     * @param  list<UploadedFile>  $attachments
     */
    public function handle(Ticket $ticket, User $author, ?string $body, bool $isInternal = false, array $attachments = [], ?UploadedFile $voiceNote = null): TicketComment
    {
        $comment = $ticket->comments()->make(['body' => $body ?? '', 'is_internal' => $isInternal]);
        $comment->author()->associate($author);
        $comment->save();

        $this->storeAttachments->handle($ticket, $author, $attachments, $voiceNote, $comment);

        $isRequester = $ticket->requester->is($author);

        if (! $isInternal && ! $isRequester && $author->isStaff() && $ticket->first_responded_at === null) {
            $this->sla->recordFirstResponse($ticket);
            $ticket->save();
        }

        if ($isRequester && ! $ticket->status->isActive()) {
            $this->updater->apply($ticket, ['status' => TicketStatus::Open], $author);
        }

        $ticket->recordActivity(
            TicketActivityType::Commented,
            $isInternal ? 'Added an internal note' : 'Replied to the ticket',
            $author,
        );

        $this->notifyParticipants($ticket, $comment, $isRequester);

        $this->workflows->run(WorkflowTrigger::CommentAdded, $ticket);

        return $comment;
    }

    /**
     * Let the other side of the conversation know about a public reply.
     */
    private function notifyParticipants(Ticket $ticket, TicketComment $comment, bool $isRequester): void
    {
        if ($comment->is_internal) {
            return;
        }

        $recipient = $isRequester ? $ticket->assignee : $ticket->requester;

        if ($recipient !== null && ! $recipient->is($comment->author)) {
            $recipient->notify(new TicketReplied($comment));
        }
    }
}
