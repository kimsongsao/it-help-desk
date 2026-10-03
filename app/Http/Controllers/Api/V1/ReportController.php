<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Feature;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Services\Reports\HelpDeskReport;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * Get the help desk performance report for a period.
     */
    public function show(ReportRequest $request): JsonResponse
    {
        $report = new HelpDeskReport($request->from(), $request->to(), $request->user()->organization_id, $request->timezone());

        return response()->json([
            'data' => [
                'period' => [
                    'from' => $report->from->setTimezone($report->timezone)->toDateString(),
                    'to' => $report->to->setTimezone($report->timezone)->toDateString(),
                    'timezone' => $report->timezone,
                ],
                'summary' => $report->summary(),
                'daily_volume' => $report->dailyVolume(),
                'by_priority' => $report->byPriority(),
                'by_status' => $report->byStatus(),
                'by_category' => $report->byCategory(),
                'teams' => $request->user()->organization->hasFeature(Feature::Teams)
                    ? $report->teamPerformance()->map(fn ($team) => [
                        'id' => $team->id,
                        'name' => $team->name,
                        'members' => $team->members_count,
                        'open' => $team->open_count,
                        'created' => $team->created_count,
                        'resolved' => $team->resolved_count,
                        'breached' => $team->breached_count,
                        'avg_resolution_hours' => $team->avg_resolution_hours,
                    ])
                    : [],
                'agents' => $report->agentPerformance()->map(fn ($agent) => [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'open' => $agent->open_tickets_count,
                    'assigned' => $agent->assigned_count,
                    'resolved' => $agent->resolved_count,
                    'breached' => $agent->breached_count,
                    'avg_resolution_hours' => $agent->avg_resolution_hours,
                ]),
            ],
        ]);
    }
}
