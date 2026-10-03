<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\CreateTicket;
use App\Actions\Tickets\UpdateTicket;
use App\Enums\Feature;
use App\Http\Requests\ListTicketsRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Category;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TicketController extends Controller
{
    /**
     * List the tickets visible to the current user.
     */
    public function index(ListTicketsRequest $request): View
    {
        $filters = $request->validated();

        $tickets = Ticket::visibleTo($request->user())
            ->filter($filters, $request->user())
            ->with(['requester', 'assignee', 'category', 'team'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statusCounts = Ticket::visibleTo($request->user())
            ->filter(Arr::except($filters, 'status'), $request->user())
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('tickets.index', [
            'tickets' => $tickets,
            'statusCounts' => $statusCounts,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(),
            'teams' => $this->teamsFor($request->user()),
        ]);
    }

    /**
     * Show the form for submitting a new ticket.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Ticket::class);

        return view('tickets.create', [
            'categories' => Category::orderBy('name')->get(),
            'selectedCategory' => $request->integer('category') ?: null,
        ]);
    }

    /**
     * Store a newly submitted ticket.
     */
    public function store(StoreTicketRequest $request, CreateTicket $createTicket): RedirectResponse
    {
        $ticket = $createTicket->handle($request->user(), $request->ticketAttributes(), $request->uploadedAttachments(), $request->file('voice_note'));

        return redirect()->route('tickets.show', $ticket)
            ->with('status', __('Ticket :reference submitted.', ['reference' => $ticket->reference]));
    }

    /**
     * Display the ticket, its conversation and its history.
     */
    public function show(Request $request, Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        $user = $request->user();
        $canSeeInternal = $user->can('commentInternal', $ticket);

        $ticket->load(['requester.department', 'assignee', 'category', 'team', 'slaPolicy', 'ticketAttachments', 'escalations.escalatedBy', 'escalations.escalatedTo']);

        $comments = $ticket->comments()
            ->with(['author', 'attachments'])
            ->unless($canSeeInternal, fn ($query) => $query->where('is_internal', false))
            ->oldest()
            ->get();

        $activities = $user->isStaff()
            ? $ticket->activities()->with('user')->latest()->latest('id')->get()
            : collect();

        $needsAgents = $user->can('assign', $ticket) || $user->can('escalate', $ticket);

        return view('tickets.show', [
            'ticket' => $ticket,
            'comments' => $comments,
            'activities' => $activities,
            'canSeeInternal' => $canSeeInternal,
            'categories' => $user->can('update', $ticket) ? Category::orderBy('name')->get() : collect(),
            'agents' => $needsAgents ? User::assignable()->with('teams')->orderBy('name')->get() : collect(),
            'teams' => $user->can('assign', $ticket) ? $this->teamsFor($user) : collect(),
        ]);
    }

    /**
     * Update the ticket's triage details.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket, UpdateTicket $updateTicket): RedirectResponse
    {
        $updateTicket->handle($ticket, $request->validated(), $request->user());

        return redirect()->route('tickets.show', $ticket)
            ->with('status', __('Ticket updated.'));
    }

    /**
     * Get the teams the user can filter or route by, when the organization's plan includes teams.
     *
     * @return Collection<int, Team>
     */
    private function teamsFor(User $user): Collection
    {
        return $user->isStaff() && $user->organization->hasFeature(Feature::Teams)
            ? Team::orderBy('name')->get()
            : collect();
    }

    /**
     * Delete the ticket.
     */
    public function destroy(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('delete', $ticket);

        $ticket->delete();

        return redirect()->route('tickets.index')
            ->with('status', __('Ticket :reference deleted.', ['reference' => $ticket->reference]));
    }
}
