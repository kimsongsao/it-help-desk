<?php

namespace App\Http\Controllers;

use App\Enums\Feature;
use App\Http\Requests\UpdateOrganizationSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationSettingsController extends Controller
{
    /**
     * Show the organization's settings, plan and usage.
     */
    public function edit(Request $request): View
    {
        $organization = $request->user()->organization;

        return view('organization.edit', [
            'organization' => $organization,
            'features' => Feature::cases(),
            'agentCount' => $organization->agentCount(),
            'userCount' => $organization->users()->count(),
            'ticketCount' => $organization->tickets()->count(),
        ]);
    }

    /**
     * Update the organization's settings.
     */
    public function update(UpdateOrganizationSettingsRequest $request): RedirectResponse
    {
        $request->user()->organization->update($request->validated());

        return redirect()->route('organization.edit')->with('status', __('Organization settings saved.'));
    }
}
