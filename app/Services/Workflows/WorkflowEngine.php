<?php

namespace App\Services\Workflows;

use App\Actions\Tickets\EscalateTicket;
use App\Enums\EscalationType;
use App\Enums\TicketActivityType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\WorkflowActionType;
use App\Enums\WorkflowField;
use App\Enums\WorkflowOperator;
use App\Enums\WorkflowTrigger;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workflow;
use App\Services\TicketUpdater;
use Illuminate\Support\Str;

class WorkflowEngine
{
    public function __construct(
        private TicketUpdater $updater,
        private EscalateTicket $escalateTicket,
    ) {}

    /**
     * Run every active workflow for the trigger against the ticket.
     *
     * Changes made by workflow actions do not trigger further workflows.
     *
     * @param  list<string>  $changedAttributes
     * @return list<Workflow> The workflows that matched and ran.
     */
    public function run(WorkflowTrigger $trigger, Ticket $ticket, array $changedAttributes = []): array
    {
        $applied = [];

        foreach (Workflow::runnableFor($trigger)->where('organization_id', $ticket->organization_id)->get() as $workflow) {
            if (! $this->matches($workflow, $ticket, $changedAttributes)) {
                continue;
            }

            foreach ($workflow->actions as $action) {
                $this->execute($action, $ticket);
            }

            $workflow->recordRun();
            $ticket->recordActivity(TicketActivityType::WorkflowApplied, 'Workflow ":name" applied', replace: ['name' => $workflow->name], properties: ['workflow_id' => $workflow->id]);
            $applied[] = $workflow;

            if ($workflow->stop_processing) {
                break;
            }
        }

        return $applied;
    }

    /**
     * Determine if the ticket satisfies the workflow's conditions.
     *
     * @param  list<string>  $changedAttributes
     */
    public function matches(Workflow $workflow, Ticket $ticket, array $changedAttributes = []): bool
    {
        if ($workflow->conditions === []) {
            return true;
        }

        $results = array_map(
            fn (array $condition): bool => $this->evaluate($condition, $ticket, $changedAttributes),
            $workflow->conditions,
        );

        return $workflow->match_type === 'any'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    /**
     * Evaluate a single condition against the ticket.
     *
     * @param  array{field: string, operator: string, value?: ?string}  $condition
     * @param  list<string>  $changedAttributes
     */
    private function evaluate(array $condition, Ticket $ticket, array $changedAttributes): bool
    {
        $field = WorkflowField::from($condition['field']);
        $operator = WorkflowOperator::from($condition['operator']);

        if ($operator === WorkflowOperator::Changed) {
            return in_array($field->value, $changedAttributes, true);
        }

        $actual = Str::lower((string) $this->valueOf($field, $ticket));
        $expected = Str::lower((string) ($condition['value'] ?? ''));

        return match ($operator) {
            WorkflowOperator::Equals => $actual === $expected,
            WorkflowOperator::NotEquals => $actual !== $expected,
            WorkflowOperator::Contains => $expected !== '' && str_contains($actual, $expected),
            WorkflowOperator::NotContains => $expected === '' || ! str_contains($actual, $expected),
            WorkflowOperator::IsEmpty => $actual === '',
            WorkflowOperator::IsNotEmpty => $actual !== '',
        };
    }

    /**
     * Read the value of a workflow field from the ticket.
     */
    private function valueOf(WorkflowField $field, Ticket $ticket): string|int|null
    {
        return match ($field) {
            WorkflowField::Status => $ticket->status->value,
            WorkflowField::Priority => $ticket->priority->value,
            WorkflowField::Category => $ticket->category_id,
            WorkflowField::Assignee => $ticket->assignee_id,
            WorkflowField::Subject => $ticket->subject,
            WorkflowField::Description => $ticket->description,
            WorkflowField::RequesterDepartment => $ticket->requester?->department,
            WorkflowField::EscalationLevel => $ticket->escalation_level,
        };
    }

    /**
     * Perform a single workflow action on the ticket. Actions whose target no longer exists are skipped.
     *
     * @param  array{type: string, value?: ?string}  $action
     */
    private function execute(array $action, Ticket $ticket): void
    {
        $value = $action['value'] ?? null;

        match (WorkflowActionType::from($action['type'])) {
            WorkflowActionType::SetStatus => $this->setStatus($ticket, TicketStatus::tryFrom((string) $value)),
            WorkflowActionType::SetPriority => ($priority = TicketPriority::tryFrom((string) $value))
                ? $this->updater->apply($ticket, ['priority' => $priority], null)
                : null,
            WorkflowActionType::SetCategory => Category::whereKey($value)->where('organization_id', $ticket->organization_id)->exists()
                ? $this->updater->apply($ticket, ['category_id' => (int) $value], null)
                : null,
            WorkflowActionType::AssignTo => $this->assign($ticket, User::assignable()->where('organization_id', $ticket->organization_id)->find($value)),
            WorkflowActionType::AssignLeastBusy => $this->assign($ticket, User::assignable()
                ->where('organization_id', $ticket->organization_id)
                ->withOpenTicketCount()
                ->orderBy('open_tickets_count')
                ->orderBy('id')
                ->first()),
            WorkflowActionType::AddInternalNote => $ticket->comments()->create(['body' => (string) $value, 'is_internal' => true]),
            WorkflowActionType::Escalate => $this->escalateTicket->handle(
                $ticket,
                (string) $value,
                EscalationType::Automatic,
                escalateTo: $ticket->slaPolicy?->escalateTo,
            ),
        };
    }

    /**
     * Move the ticket to the status when the transition is allowed.
     */
    private function setStatus(Ticket $ticket, ?TicketStatus $status): void
    {
        if ($status !== null && $ticket->status->canTransitionTo($status)) {
            $this->updater->apply($ticket, ['status' => $status], null);
        }
    }

    /**
     * Assign the ticket to the user when one was found.
     */
    private function assign(Ticket $ticket, ?User $user): void
    {
        if ($user !== null) {
            $this->updater->apply($ticket, ['assignee_id' => $user->id], null);
        }
    }
}
