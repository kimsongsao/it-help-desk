<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\JoinOrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class JoinOrganizationController extends Controller
{
    /**
     * Show the sign-up form for employees joining a company's help desk.
     */
    public function create(Request $request, Organization $organization): View
    {
        abort_unless($this->isOpen($organization), 404);

        $request->session()->put('joining_organization_id', $organization->id);

        return view('auth.join', ['organization' => $organization]);
    }

    /**
     * Register the employee as a requester in the company.
     */
    public function store(JoinOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        abort_unless($this->isOpen($organization), 404);

        $user = new User($request->validated());
        $user->organization()->associate($organization);
        $user->locale = app()->getLocale();
        $user->save();

        Auth::login($user);
        $request->session()->forget('joining_organization_id');
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /**
     * Determine if the organization accepts employees signing themselves up.
     */
    private function isOpen(Organization $organization): bool
    {
        return $organization->is_active
            && ($organization->allow_self_registration || $organization->allowed_email_domains !== []);
    }
}
