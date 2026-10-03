<?php

namespace App\Enums;

enum WorkflowField: string
{
    case Status = 'status';
    case Priority = 'priority';
    case Category = 'category_id';
    case Assignee = 'assignee_id';
    case Team = 'team_id';
    case Subject = 'subject';
    case Description = 'description';
    case RequesterDepartment = 'requester_department';
    case EscalationLevel = 'escalation_level';

    /**
     * Get the translated label for the field.
     */
    public function label(?string $locale = null): string
    {
        return __(match ($this) {
            self::Status => 'Status',
            self::Priority => 'Priority',
            self::Category => 'Category',
            self::Assignee => 'Assignee',
            self::Team => 'Team',
            self::Subject => 'Subject',
            self::Description => 'Description',
            self::RequesterDepartment => 'Requester department',
            self::EscalationLevel => 'Escalation level',
        }, locale: $locale);
    }
}
