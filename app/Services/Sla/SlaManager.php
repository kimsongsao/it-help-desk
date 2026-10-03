<?php

namespace App\Services\Sla;

use App\Models\SlaPolicy;
use App\Models\Ticket;
use Carbon\CarbonImmutable;

class SlaManager
{
    public function __construct(private BusinessHoursCalculator $calculator) {}

    /**
     * Attach the matching SLA policy to the ticket and calculate its due dates.
     *
     * The ticket is not saved; callers persist it alongside their own changes.
     */
    public function applyPolicy(Ticket $ticket): void
    {
        $policy = SlaPolicy::applicableTo($ticket)->first();

        $ticket->slaPolicy()->associate($policy);

        if ($policy === null) {
            $ticket->forceFill([
                'first_response_at_risk_at' => null,
                'first_response_due_at' => null,
                'resolution_at_risk_at' => null,
                'resolution_due_at' => null,
            ]);

            return;
        }

        $start = CarbonImmutable::instance($ticket->created_at ?? now());
        $firstResponseDue = $this->calculator->dueAt($start, $policy->first_response_minutes, $policy);
        $resolutionDue = $this->calculator->dueAt($start, $policy->resolution_minutes, $policy);

        $ticket->forceFill([
            'first_response_at_risk_at' => $this->atRiskAt($start, $firstResponseDue),
            'first_response_due_at' => $firstResponseDue,
            'resolution_at_risk_at' => $this->atRiskAt($start, $resolutionDue),
            'resolution_due_at' => $resolutionDue,
        ]);
    }

    /**
     * Get the moment a target becomes "at risk": once the configured share of its time has elapsed.
     */
    private function atRiskAt(CarbonImmutable $start, CarbonImmutable $dueAt): CarbonImmutable
    {
        $allowedSeconds = (int) $start->diffInSeconds($dueAt);

        return $start->addSeconds((int) round($allowedSeconds * config('helpdesk.sla_at_risk_threshold')));
    }

    /**
     * Stop the SLA clock, e.g. while waiting on the requester.
     */
    public function pause(Ticket $ticket): void
    {
        $ticket->sla_paused_at ??= now();
    }

    /**
     * Restart the SLA clock, pushing the open targets back by the time spent paused.
     */
    public function resume(Ticket $ticket): void
    {
        if ($ticket->sla_paused_at === null) {
            return;
        }

        $pausedSeconds = (int) $ticket->sla_paused_at->diffInSeconds(now());

        $targets = $ticket->first_responded_at === null
            ? ['first_response_at_risk_at', 'first_response_due_at', 'resolution_at_risk_at', 'resolution_due_at']
            : ['resolution_at_risk_at', 'resolution_due_at'];

        foreach ($targets as $attribute) {
            $ticket->{$attribute} = $ticket->{$attribute}?->addSeconds($pausedSeconds);
        }

        $ticket->sla_paused_at = null;
    }

    /**
     * Record that support has responded to the requester.
     */
    public function recordFirstResponse(Ticket $ticket): void
    {
        $ticket->first_responded_at ??= now();
    }
}
