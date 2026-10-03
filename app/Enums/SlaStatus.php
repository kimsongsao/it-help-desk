<?php

namespace App\Enums;

enum SlaStatus: string
{
    case OnTrack = 'on_track';
    case AtRisk = 'at_risk';
    case Breached = 'breached';
    case Paused = 'paused';
    case Met = 'met';

    /**
     * Get the translated label for the SLA status.
     */
    public function label(?string $locale = null): string
    {
        return __(match ($this) {
            self::OnTrack => 'On track',
            self::AtRisk => 'At risk',
            self::Breached => 'Breached',
            self::Paused => 'Paused',
            self::Met => 'Met',
        }, locale: $locale);
    }

    /**
     * Get the Tabler color used to render the SLA status.
     */
    public function color(): string
    {
        return match ($this) {
            self::OnTrack => 'green',
            self::AtRisk => 'orange',
            self::Breached => 'red',
            self::Paused => 'purple',
            self::Met => 'teal',
        };
    }
}
