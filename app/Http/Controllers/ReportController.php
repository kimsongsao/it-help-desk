<?php

namespace App\Http\Controllers;

use App\Enums\Feature;
use App\Http\Requests\ReportRequest;
use App\Models\Ticket;
use App\Services\Reports\HelpDeskReport;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Show the help desk performance report.
     */
    public function index(ReportRequest $request): View
    {
        $report = new HelpDeskReport($request->from(), $request->to(), $request->user()->organization_id, $request->timezone());

        return view('reports.index', [
            'report' => $report,
            'summary' => $report->summary(),
            'dailyVolume' => $report->dailyVolume(),
            'byPriority' => $report->byPriority(),
            'byStatus' => $report->byStatus(),
            'byCategory' => $report->byCategory(),
            'agents' => $report->agentPerformance(),
            'teams' => $request->user()->organization->hasFeature(Feature::Teams) ? $report->teamPerformance() : collect(),
        ]);
    }

    /**
     * Download the tickets created in the period as CSV.
     */
    public function export(ReportRequest $request): StreamedResponse
    {
        $report = new HelpDeskReport($request->from(), $request->to(), $request->user()->organization_id, $request->timezone());
        $filename = "tickets-{$report->from->setTimezone($report->timezone)->toDateString()}-to-{$report->to->setTimezone($report->timezone)->toDateString()}.csv";

        return response()->streamDownload(function () use ($report): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'Reference', 'Subject', 'Status', 'Priority', 'Category', 'Requester', 'Department', 'Assignee',
                'Team', 'Created', 'First response', 'Resolved', 'First response SLA', 'Resolution SLA', 'Escalation level',
            ]);

            $report->ticketsForExport()->lazyById()->each(function (Ticket $ticket) use ($output, $report): void {
                fputcsv($output, [
                    $ticket->reference,
                    $ticket->subject,
                    $ticket->status->label(),
                    $ticket->priority->label(),
                    $ticket->category?->name,
                    $ticket->requester->name,
                    $ticket->requester->department?->name,
                    $ticket->assignee?->name,
                    $ticket->team?->name,
                    $ticket->created_at->setTimezone($report->timezone)->toDateTimeString(),
                    $ticket->first_responded_at?->setTimezone($report->timezone)->toDateTimeString(),
                    $ticket->resolved_at?->setTimezone($report->timezone)->toDateTimeString(),
                    $ticket->firstResponseSlaStatus()?->label(),
                    $ticket->resolutionSlaStatus()?->label(),
                    $ticket->escalation_level,
                ]);
            });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
