<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Resolved = 'resolved';
    case Closed = 'closed';

    /**
     * Get the translated label for the status.
     */
    public function label(?string $locale = null): string
    {
        return __(match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::OnHold => 'On Hold',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        }, locale: $locale);
    }

    /**
     * Get the Tabler color used to render the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Open => 'blue',
            self::InProgress => 'yellow',
            self::OnHold => 'purple',
            self::Resolved => 'green',
            self::Closed => 'secondary',
        };
    }

    /**
     * Determine if the ticket still requires work.
     */
    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }

    /**
     * Get the statuses that still require work.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Open, self::InProgress, self::OnHold];
    }

    /**
     * Get the statuses a ticket may move to from this status.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::InProgress, self::OnHold, self::Resolved, self::Closed],
            self::InProgress => [self::Open, self::OnHold, self::Resolved, self::Closed],
            self::OnHold => [self::Open, self::InProgress, self::Resolved, self::Closed],
            self::Resolved => [self::Open, self::InProgress, self::Closed],
            self::Closed => [self::Open],
        };
    }

    /**
     * Determine if the ticket may move from this status to the given status.
     */
    public function canTransitionTo(self $status): bool
    {
        return $status === $this || in_array($status, $this->allowedTransitions(), true);
    }
}
