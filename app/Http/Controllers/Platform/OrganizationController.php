<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Organizations\CreateOrganization;
use App\Enums\HelpDeskTemplate;
use App\Enums\Plan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreOrganizationRequest;
use App\Http\Requests\Platform\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    /**
     * List every organization on the platform with its plan and usage.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'plan' => ['nullable', 'in:'.implode(',', array_column(Plan::cases(), 'value'))],
        ]);

        $organizations = Organization::query()
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->whereLike('name', "%{$search}%"))
            ->when($filters['plan'] ?? null, fn ($query, string $plan) => $query->where('plan', $plan))
            ->withCount([
                'users' => fn ($query) => $query->withoutGlobalScope('organization'),
                'tickets' => fn ($query) => $query->withoutGlobalScope('organization'),
            ])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('platform.organizations.index', [
            'organizations' => $organizations,
            'filters' => $filters,
            'totals' => [
                'organizations' => Organization::count(),
                'users' => User::withoutGlobalScope('organization')->count(),
                'tickets' => Ticket::withoutGlobalScope('organization')->count(),
                'byPlan' => Organization::toBase()->selectRaw('plan, count(*) as total')->groupBy('plan')->pluck('total', 'plan'),
            ],
        ]);
    }

    /**
     * Show the form for onboarding a company manually.
     */
    public function create(): View
    {
        return view('platform.organizations.create');
    }

    /**
     * Create a company workspace and its administrator.
     */
    public function store(StoreOrganizationRequest $request, CreateOrganization $createOrganization): RedirectResponse
    {
        $owner = $createOrganization->handle(
            $request->safe()->only(['company_name', 'name', 'email', 'password']),
            Plan::from($request->validated('plan')),
            template: HelpDeskTemplate::from($request->validated('template')),
        );

        return redirect()->route('platform.organizations.edit', $owner->organization)
            ->with('status', __(':name was created.', ['name' => $owner->organization->name]));
    }

    /**
     * Show an organization's plan, status and usage.
     */
    public function edit(Organization $organization): View
    {
        return view('platform.organizations.edit', [
            'organization' => $organization,
            'agentCount' => $organization->agentCount(),
            'administrators' => User::withoutGlobalScope('organization')
                ->whereBelongsTo($organization)
                ->whereHas('role', fn ($query) => $query->withoutGlobalScope('organization')->where('slug', 'admin'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Change an organization's plan or suspend it.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        if ($organization->is($request->user()->organization) && ! $request->validated('is_active')) {
            return back()->withErrors(['is_active' => __('You cannot suspend your own organization.')]);
        }

        $organization->plan = Plan::from($request->validated('plan'));
        $organization->is_active = $request->validated('is_active');
        $organization->save();

        return redirect()->route('platform.organizations.edit', $organization)->with('status', __(':name was updated.', ['name' => $organization->name]));
    }
}
