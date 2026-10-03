<?php

namespace App\Http\Controllers;

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
        $report = new HelpDeskReport($request->from(), $request->to(), $request->user()->organization_id);

        return view('reports.index', [
            'report' => $report,
            'summary' => $report->summary(),
            'dailyVolume' => $report->dailyVolume(),
            'byPriority' => $report->byPriority(),
            'byStatus' => $report->byStatus(),
            'byCategory' => $report->byCategory(),
            'agents' => $report->agentPerformance(),
        ]);
    }

    /**
     * Download the tickets created in the period as CSV.
     */
    public function export(ReportRequest $request): StreamedResponse
    {
        $report = new HelpDeskReport($request->from(), $request->to(), $request->user()->organization_id);
        $filename = "tickets-{$report->from->toDateString()}-to-{$report->to->toDateString()}.csv";

        return response()->streamDownload(function () use ($report): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'Reference', 'Subject', 'Status', 'Priority', 'Category', 'Requester', 'Department', 'Assignee',
                'Created', 'First response', 'Resolved', 'First response SLA', 'Resolution SLA', 'Escalation level',
            ]);

            $report->ticketsForExport()->lazyById()->each(function (Ticket $ticket) use ($output): void {
                fputcsv($output, [
                    $ticket->reference,
                    $ticket->subject,
                    $ticket->status->label(),
                    $ticket->priority->label(),
                    $ticket->category?->name,
                    $ticket->requester->name,
                    $ticket->requester->department,
                    $ticket->assignee?->name,
                    $ticket->created_at->toDateTimeString(),
                    $ticket->first_responded_at?->toDateTimeString(),
                    $ticket->resolved_at?->toDateTimeString(),
                    $ticket->firstResponseSlaStatus()?->label(),
                    $ticket->resolutionSlaStatus()?->label(),
                    $ticket->escalation_level,
                ]);
            });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
