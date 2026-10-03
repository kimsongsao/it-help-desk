<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    /**
     * Get the translated label for the priority.
     */
    public function label(?string $locale = null): string
    {
        return __(ucfirst($this->value), locale: $locale);
    }

    /**
     * Get the Tabler color used to render the priority.
     */
    public function color(): string
    {
        return match ($this) {
            self::Low => 'secondary',
            self::Medium => 'azure',
            self::High => 'orange',
            self::Critical => 'red',
        };
    }

    /**
     * Get the next higher priority, used when a ticket is escalated.
     */
    public function raised(): self
    {
        return match ($this) {
            self::Low => self::Medium,
            self::Medium => self::High,
            self::High, self::Critical => self::Critical,
        };
    }
}
