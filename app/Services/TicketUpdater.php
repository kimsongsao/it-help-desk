<?php

namespace App\Services;

use App\Enums\TicketActivityType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Services\Sla\SlaManager;
use Illuminate\Support\Arr;

class TicketUpdater
{
    /**
     * The ticket attributes that can be changed through the updater.
     *
     * @var list<string>
     */
    private const array Attributes = ['status', 'priority', 'category_id', 'assignee_id'];

    public function __construct(private SlaManager $sla) {}

    /**
     * Apply triage changes to the ticket, keeping its SLA, activity log and notifications in sync.
     *
     * This does not run workflows, so it is safe to call from workflow actions.
     *
     * @param  array{status?: TicketStatus|string, priority?: TicketPriority|string, category_id?: int|string|null, assignee_id?: int|string|null}  $changes
     * @return list<string> The attributes that changed.
     */
    public function apply(Ticket $ticket, array $changes, ?User $actor): array
    {
        $original = [
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'category_id' => $ticket->category_id,
            'assignee_id' => $ticket->assignee_id,
        ];

        $ticket->fill(Arr::only($changes, self::Attributes));

        $changed = array_values(array_intersect(self::Attributes, array_keys($ticket->getDirty())));

        if ($changed === []) {
            return [];
        }

        if (in_array('priority', $changed, true) || in_array('category_id', $changed, true)) {
            $this->sla->applyPolicy($ticket);
        }

        if (in_array('status', $changed, true)) {
            $this->syncSlaClock($ticket, $original['status']);
        }

        $ticket->save();

        $this->recordActivities($ticket, $changed, $original, $actor);

        if (in_array('assignee_id', $changed, true) && $ticket->assignee !== null && ! $ticket->assignee->is($actor)) {
            $ticket->assignee->notify(new TicketAssigned($ticket, $actor));
        }

        return $changed;
    }

    /**
     * Pause, resume or complete the SLA clock after a status change.
     */
    private function syncSlaClock(Ticket $ticket, TicketStatus $previousStatus): void
    {
        if ($ticket->status === TicketStatus::OnHold) {
            $this->sla->pause($ticket);

            return;
        }

        if ($previousStatus === TicketStatus::OnHold) {
            $this->sla->resume($ticket);
        }

        if (! $ticket->status->isActive()) {
            $this->sla->recordFirstResponse($ticket);
        }
    }

    /**
     * Write an activity entry for each changed attribute.
     *
     * @param  list<string>  $changed
     * @param  array{status: TicketStatus, priority: TicketPriority, category_id: ?int, assignee_id: ?int}  $original
     */
    private function recordActivities(Ticket $ticket, array $changed, array $original, ?User $actor): void
    {
        $ticket->load(['category', 'assignee']);

        foreach ($changed as $attribute) {
            [$type, $description, $replace] = match ($attribute) {
                'status' => [TicketActivityType::StatusChanged, 'Status changed from :from to :to', [
                    'from' => $original['status']->label('en'),
                    'to' => $ticket->status->label('en'),
                ]],
                'priority' => [TicketActivityType::PriorityChanged, 'Priority changed from :from to :to', [
                    'from' => $original['priority']->label('en'),
                    'to' => $ticket->priority->label('en'),
                ]],
                'category_id' => [TicketActivityType::CategoryChanged, 'Category changed from :from to :to', [
                    'from' => Category::find($original['category_id'])?->name ?? 'none',
                    'to' => $ticket->category?->name ?? 'none',
                ]],
                'assignee_id' => $ticket->assignee
                    ? [TicketActivityType::Assigned, 'Assigned to :name', ['name' => $ticket->assignee->name]]
                    : [TicketActivityType::Assigned, 'Unassigned', []],
            };

            $ticket->recordActivity($type, $description, $actor, $replace);
        }
    }
}
