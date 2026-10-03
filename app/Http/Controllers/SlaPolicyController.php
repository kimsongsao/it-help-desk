<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSlaPolicyRequest;
use App\Http\Requests\UpdateSlaPolicyRequest;
use App\Models\Category;
use App\Models\SlaPolicy;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SlaPolicyController extends Controller
{
    /**
     * List the SLA policies.
     */
    public function index(): View
    {
        return view('sla-policies.index', [
            'policies' => SlaPolicy::with(['category', 'escalateTo'])
                ->withCount('tickets')
                ->orderByDesc('is_active')
                ->orderByRaw('category_id IS NULL, priority IS NULL')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating an SLA policy.
     */
    public function create(): View
    {
        return $this->form(new SlaPolicy([
            'business_hours_start' => '09:00',
            'business_hours_end' => '17:00',
            'business_days' => [1, 2, 3, 4, 5],
            'escalate_on_breach' => true,
            'is_active' => true,
        ]));
    }

    /**
     * Store a new SLA policy.
     */
    public function store(StoreSlaPolicyRequest $request): RedirectResponse
    {
        SlaPolicy::create($request->validated());

        return redirect()->route('sla-policies.index')->with('status', __('SLA policy created. It applies to new tickets and to tickets whose priority or category changes.'));
    }

    /**
     * Show the form for editing an SLA policy.
     */
    public function edit(SlaPolicy $slaPolicy): View
    {
        return $this->form($slaPolicy);
    }

    /**
     * Update the SLA policy.
     */
    public function update(UpdateSlaPolicyRequest $request, SlaPolicy $slaPolicy): RedirectResponse
    {
        $slaPolicy->update($request->validated());

        return redirect()->route('sla-policies.index')->with('status', __('SLA policy updated.'));
    }

    /**
     * Delete the SLA policy. Its tickets keep their current due dates.
     */
    public function destroy(SlaPolicy $slaPolicy): RedirectResponse
    {
        $slaPolicy->delete();

        return redirect()->route('sla-policies.index')->with('status', __('SLA policy deleted.'));
    }

    /**
     * Render the create/edit form.
     */
    private function form(SlaPolicy $policy): View
    {
        return view('sla-policies.form', [
            'policy' => $policy,
            'categories' => Category::orderBy('name')->get(),
            'agents' => User::assignable()->orderBy('name')->get(),
        ]);
    }
}
