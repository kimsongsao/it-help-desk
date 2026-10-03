<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Switch the interface language and remember it for the signed in user.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(array_keys(config('helpdesk.locales')))],
        ]);

        $request->session()->put('locale', $validated['locale']);
        $request->user()?->forceFill(['locale' => $validated['locale']])->save();

        return back();
    }
}
