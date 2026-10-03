<?php

namespace App\Enums;

enum WorkflowActionType: string
{
    case SetStatus = 'set_status';
    case SetPriority = 'set_priority';
    case SetCategory = 'set_category';
    case AssignTo = 'assign_to';
    case AssignLeastBusy = 'assign_least_busy';
    case AddInternalNote = 'add_internal_note';
    case Escalate = 'escalate';

    /**
     * Get the translated label for the action.
     */
    public function label(?string $locale = null): string
    {
        return __(match ($this) {
            self::SetStatus => 'Set status to',
            self::SetPriority => 'Set priority to',
            self::SetCategory => 'Set category to',
            self::AssignTo => 'Assign to',
            self::AssignLeastBusy => 'Assign to least busy agent',
            self::AddInternalNote => 'Add internal note',
            self::Escalate => 'Escalate with reason',
        }, locale: $locale);
    }

    /**
     * Determine if the action requires a value.
     */
    public function needsValue(): bool
    {
        return $this !== self::AssignLeastBusy;
    }
}
