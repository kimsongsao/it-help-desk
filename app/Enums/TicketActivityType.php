<?php

namespace App\Enums;

enum TicketActivityType: string
{
    case Created = 'created';
    case StatusChanged = 'status_changed';
    case PriorityChanged = 'priority_changed';
    case CategoryChanged = 'category_changed';
    case Assigned = 'assigned';
    case TeamChanged = 'team_changed';
    case Commented = 'commented';
    case Escalated = 'escalated';
    case SlaBreached = 'sla_breached';
    case WorkflowApplied = 'workflow_applied';

    /**
     * Get the Tabler icon used in the activity timeline.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Created => 'ti-plus',
            self::StatusChanged => 'ti-progress',
            self::PriorityChanged => 'ti-flag',
            self::CategoryChanged => 'ti-category',
            self::Assigned => 'ti-user-check',
            self::TeamChanged => 'ti-users-group',
            self::Commented => 'ti-message',
            self::Escalated => 'ti-arrow-big-up-lines',
            self::SlaBreached => 'ti-alarm',
            self::WorkflowApplied => 'ti-git-branch',
        };
    }

    /**
     * Get the Tabler color used in the activity timeline.
     */
    public function color(): string
    {
        return match ($this) {
            self::Escalated, self::SlaBreached => 'red',
            self::WorkflowApplied => 'purple',
            self::Assigned, self::TeamChanged => 'green',
            default => 'blue',
        };
    }
}
