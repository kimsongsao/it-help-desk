<?php

namespace App\Http\Controllers;

use App\Actions\Profile\UpdateAvatar;
use App\Enums\SocialProvider;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\Department;
use App\Models\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the signed in user's profile.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'socialAccounts' => $user->socialAccounts()->get()->keyBy(fn (SocialAccount $account) => $account->provider->value),
            'providers' => SocialProvider::cases(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the user's details, language and photo.
     */
    public function update(UpdateProfileRequest $request, UpdateAvatar $updateAvatar): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->safe()->only(['name', 'email', 'department_id']));
        $user->locale = $request->validated('locale');
        $user->timezone = $request->validated('timezone');

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($request->hasFile('avatar')) {
            $updateAvatar->handle($user, $request->file('avatar'));
        }

        $request->session()->put('locale', $user->locale);

        return redirect()->route('profile.edit')->with('status', __('Profile updated.', locale: $user->locale));
    }

    /**
     * Remove the user's profile photo.
     */
    public function destroyAvatar(Request $request, UpdateAvatar $updateAvatar): RedirectResponse
    {
        $updateAvatar->remove($request->user());

        return redirect()->route('profile.edit')->with('status', __('Profile photo removed.'));
    }

    /**
     * Change or set the user's password, signing out their other sessions.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $hadPassword = $request->user()->hasPassword();

        $request->user()->forceFill(['password' => $request->validated('password')])->save();

        if ($hadPassword) {
            Auth::logoutOtherDevices($request->validated('password'));
        }

        return redirect()->route('profile.edit')->with('status', $hadPassword ? __('Password changed.') : __('Password set. You can now also log in with your email and password.'));
    }

    /**
     * Disconnect a social sign-in, as long as the user keeps another way to sign in.
     */
    public function destroySocialAccount(Request $request, SocialAccount $socialAccount): RedirectResponse
    {
        $user = $request->user();
        abort_unless($socialAccount->user_id === $user->id, 404);

        $hasOtherSignIn = ($user->hasPassword() && $user->email !== null)
            || $user->socialAccounts()->whereKeyNot($socialAccount->id)->exists();

        if (! $hasOtherSignIn) {
            return back()->withErrors(['social' => __('Set a password first, so you can still sign in after disconnecting :provider.', ['provider' => $socialAccount->provider->label()])]);
        }

        $socialAccount->delete();

        return redirect()->route('profile.edit')->with('status', __(':provider disconnected.', ['provider' => $socialAccount->provider->label()]));
    }
}
