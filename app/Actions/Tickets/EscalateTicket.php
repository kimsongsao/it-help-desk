<?php

namespace App\Actions\Tickets;

use App\Enums\EscalationType;
use App\Enums\TicketActivityType;
use App\Models\Ticket;
use App\Models\TicketEscalation;
use App\Models\User;
use App\Notifications\TicketEscalated;
use App\Services\TicketUpdater;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class EscalateTicket
{
    public function __construct(private TicketUpdater $updater) {}

    /**
     * Raise the ticket to the next escalation level, bump its priority and optionally hand it over.
     */
    public function handle(
        Ticket $ticket,
        string $reason,
        EscalationType $type,
        ?User $escalatedBy = null,
        ?User $escalateTo = null,
    ): TicketEscalation {
        $escalation = DB::transaction(function () use ($ticket, $reason, $type, $escalatedBy, $escalateTo): TicketEscalation {
            $ticket->forceFill([
                'escalation_level' => $ticket->escalation_level + 1,
                'escalated_at' => now(),
            ])->save();

            $this->updater->apply($ticket, array_filter([
                'priority' => $ticket->priority->raised(),
                'assignee_id' => $escalateTo?->id,
            ]), $escalatedBy);

            $escalation = $ticket->escalations()->make([
                'level' => $ticket->escalation_level,
                'type' => $type,
                'reason' => $reason,
            ]);
            $escalation->escalatedBy()->associate($escalatedBy);
            $escalation->escalatedTo()->associate($escalateTo);
            $escalation->save();

            $ticket->recordActivity(
                TicketActivityType::Escalated,
                $escalateTo ? 'Escalated to level :level and handed to :name: :reason' : 'Escalated to level :level: :reason',
                $escalatedBy,
                array_filter(['level' => $escalation->level, 'name' => $escalateTo?->name, 'reason' => $reason]),
            );

            return $escalation;
        });

        $recipients = collect([$escalateTo, $ticket->assignee])
            ->filter()
            ->unique('id')
            ->reject(fn (User $user): bool => $user->is($escalatedBy));

        Notification::send($recipients, new TicketEscalated($escalation));

        return $escalation;
    }
}
