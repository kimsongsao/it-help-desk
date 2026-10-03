<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Category;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeamController extends Controller
{
    /**
     * List the support teams with their members and open workload.
     */
    public function index(): View
    {
        return view('teams.index', [
            'teams' => Team::with(['members', 'categories'])
                ->withCount(['tickets as open_tickets_count' => fn ($query) => $query->active()])
                ->withCount(['tickets as unassigned_tickets_count' => fn ($query) => $query->active()->whereNull('assignee_id')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a team.
     */
    public function create(): View
    {
        return $this->form(new Team(['color' => 'blue']));
    }

    /**
     * Store a new team.
     */
    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $this->save(new Team, $request);

        return redirect()->route('teams.index')->with('status', __('Team created.'));
    }

    /**
     * Show the form for editing a team.
     */
    public function edit(Team $team): View
    {
        return $this->form($team->load(['members', 'categories']));
    }

    /**
     * Update the team, its members and the categories routed to it.
     */
    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->save($team, $request);

        return redirect()->route('teams.index')->with('status', __('Team updated.'));
    }

    /**
     * Delete the team. Its tickets stay, without a team.
     */
    public function destroy(Team $team): RedirectResponse
    {
        $team->delete();

        return redirect()->route('teams.index')->with('status', __('Team deleted.'));
    }

    /**
     * Save the team's details, members and routed categories together.
     */
    private function save(Team $team, StoreTeamRequest $request): void
    {
        DB::transaction(function () use ($team, $request): void {
            $team->fill($request->safe()->only(['name', 'description', 'color']))->save();
            $team->members()->sync($request->validated('members', []));

            Category::whereBelongsTo($team, 'defaultTeam')
                ->whereNotIn('id', $request->validated('categories', []))
                ->update(['default_team_id' => null]);
            Category::whereKey($request->validated('categories', []))->update(['default_team_id' => $team->id]);
        });
    }

    /**
     * Render the create/edit form.
     */
    private function form(Team $team): View
    {
        return view('teams.form', [
            'team' => $team,
            'agents' => User::assignable()->with('teams')->orderBy('name')->get(),
            'categories' => Category::with('defaultTeam')->orderBy('name')->get(),
        ]);
    }
}
