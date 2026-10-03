<?php

namespace App\Support;

use App\Models\User;

/**
 * The time zone dates are shown in for the current request.
 *
 * Dates are always stored and calculated in UTC; only presentation uses this time zone.
 */
class DisplayTimezone
{
    private ?string $timezone = null;

    /**
     * Get the time zone to show dates in.
     */
    public function get(): string
    {
        return $this->timezone ?? config('helpdesk.default_timezone');
    }

    /**
     * Show dates in the given time zone.
     */
    public function set(?string $timezone): void
    {
        $this->timezone = $timezone;
    }

    /**
     * Show dates in the user's own time zone, falling back to their organization's.
     */
    public function useFor(User $user): void
    {
        $this->timezone = $user->timezone ?? $user->organization?->timezone;
    }
}
