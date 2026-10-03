<?php

namespace App\Services\Sla;

use App\Models\SlaPolicy;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class BusinessHoursCalculator
{
    /**
     * The maximum number of days to search for working time before giving up.
     */
    private const int MaxDaysToSearch = 3660;

    /**
     * Calculate when a target of the given number of minutes falls due under the policy's calendar.
     *
     * Business hours and working days are read in the given time zone (the organization's); the result is UTC.
     */
    public function dueAt(CarbonInterface $start, int $minutes, SlaPolicy $policy, string $timezone = 'UTC'): CarbonImmutable
    {
        $start = CarbonImmutable::instance($start)->setTimezone($timezone);

        if (! $this->usesBusinessHours($policy)) {
            return $start->addMinutes($minutes)->utc();
        }

        $cursor = $start;
        $remaining = $minutes;

        for ($day = 0; $day < self::MaxDaysToSearch; $day++) {
            $dayStart = $cursor->setTimeFromTimeString($policy->business_hours_start);
            $dayEnd = $cursor->setTimeFromTimeString($policy->business_hours_end);

            if (in_array($cursor->dayOfWeekIso, $policy->business_days, false) && $cursor->lt($dayEnd)) {
                $cursor = $cursor->max($dayStart);
                $available = (int) $cursor->diffInMinutes($dayEnd);

                if ($remaining <= $available) {
                    return $cursor->addMinutes($remaining)->utc();
                }

                $remaining -= $available;
            }

            $cursor = $cursor->addDay()->setTimeFromTimeString($policy->business_hours_start);
        }

        return $start->addMinutes($minutes)->utc();
    }

    /**
     * Determine if the policy defines a usable business hours calendar.
     */
    private function usesBusinessHours(SlaPolicy $policy): bool
    {
        return $policy->business_hours_only
            && ! empty($policy->business_days)
            && $policy->business_hours_start < $policy->business_hours_end;
    }
}
