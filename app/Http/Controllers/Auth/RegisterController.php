<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Organizations\CreateOrganization;
use App\Enums\HelpDeskTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the company sign-up form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Create a company workspace on the free plan and sign its owner in.
     */
    public function store(RegisterRequest $request, CreateOrganization $createOrganization): RedirectResponse
    {
        $owner = $createOrganization->handle(
            $request->safe()->except(['template', 'timezone']),
            locale: app()->getLocale(),
            template: HelpDeskTemplate::from($request->validated('template')),
            timezone: $request->validated('timezone'),
        );

        Auth::login($owner);
        $request->session()->regenerate();

        return redirect()->route('organization.edit')
            ->with('status', __('Welcome! Your help desk is ready. Invite your team to get started.'));
    }
}
