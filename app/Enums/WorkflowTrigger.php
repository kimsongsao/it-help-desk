<?php

namespace App\Enums;

enum WorkflowTrigger: string
{
    case TicketCreated = 'ticket_created';
    case TicketUpdated = 'ticket_updated';
    case CommentAdded = 'comment_added';
    case SlaBreached = 'sla_breached';

    /**
     * Get the translated label for the trigger.
     */
    public function label(?string $locale = null): string
    {
        return __(match ($this) {
            self::TicketCreated => 'Ticket is created',
            self::TicketUpdated => 'Ticket is updated',
            self::CommentAdded => 'Reply is added',
            self::SlaBreached => 'SLA is breached',
        }, locale: $locale);
    }
}
