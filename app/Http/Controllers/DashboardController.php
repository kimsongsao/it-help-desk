<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the help desk dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $statusCounts = Ticket::visibleTo($user)
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $recentTickets = Ticket::visibleTo($user)
            ->with(['requester', 'assignee', 'category'])
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $myQueue = $user->isStaff()
            ? $user->assignedTickets()->active()->with(['requester', 'assignee', 'category'])->orderBy('resolution_due_at')->limit(8)->get()
            : collect();

        $slaCounts = $user->isStaff() ? [
            'unassigned' => Ticket::visibleTo($user)->active()->whereNull('assignee_id')->count(),
            'breached' => Ticket::visibleTo($user)->slaBreached()->count(),
            'at_risk' => Ticket::visibleTo($user)->slaAtRisk()->count(),
            'escalated' => Ticket::visibleTo($user)->active()->where('escalation_level', '>', 0)->count(),
        ] : null;

        $openRequests = $user->isStaff()
            ? collect()
            : $user->requestedTickets()->active()->with(['assignee', 'category'])->latest()->limit(5)->get();

        return view('dashboard', [
            'openRequests' => $openRequests,
            'categories' => $user->can('tickets.create') ? Category::orderBy('name')->get() : collect(),
            'statuses' => TicketStatus::cases(),
            'statusCounts' => $statusCounts,
            'recentTickets' => $recentTickets,
            'myQueue' => $myQueue,
            'slaCounts' => $slaCounts,
        ]);
    }
}
