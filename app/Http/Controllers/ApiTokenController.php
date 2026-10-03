<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    /**
     * List the user's personal API tokens.
     */
    public function index(Request $request): View
    {
        return view('api-tokens.index', [
            'tokens' => $request->user()->tokens()->latest()->get(),
        ]);
    }

    /**
     * Create a personal API token and show it once.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $token = $request->user()->createToken($validated['name']);

        return redirect()->route('api-tokens.index')
            ->with('plainTextToken', $token->plainTextToken)
            ->with('status', __('Token created. Copy it now, it will not be shown again.'));
    }

    /**
     * Revoke a personal API token.
     */
    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return redirect()->route('api-tokens.index')->with('status', __('Token revoked.'));
    }
}
