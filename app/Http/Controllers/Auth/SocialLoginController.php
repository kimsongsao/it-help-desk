<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginWithSocialAccount;
use App\Enums\SocialProvider;
use App\Exceptions\SocialLoginException;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\Auth\SocialIdentity;
use App\Services\Auth\TelegramLoginVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialLoginController extends Controller
{
    /**
     * Send the user to Google or Facebook to sign in.
     */
    public function redirect(SocialProvider $provider): SymfonyRedirectResponse
    {
        abort_unless($provider->usesOAuth() && $provider->isConfigured(), 404);

        return Socialite::driver($provider->value)->redirect();
    }

    /**
     * Sign the user in after Google, Facebook or Telegram sends them back.
     */
    public function callback(Request $request, SocialProvider $provider, LoginWithSocialAccount $login, TelegramLoginVerifier $telegram): RedirectResponse
    {
        abort_unless($provider->isConfigured(), 404);

        try {
            $identity = $provider->usesOAuth()
                ? $this->identityFromOAuth($provider)
                : $telegram->verify($request->query());

            $joining = Organization::find($request->session()->get('joining_organization_id'));
            $user = $login->handle($identity, $joining);
        } catch (SocialLoginException $exception) {
            return redirect()->route('login')->withErrors(['social' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors(['social' => __(':provider sign in was cancelled or failed. Please try again.', ['provider' => $provider->label()])]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Read the signed in user's details from an OAuth provider.
     */
    private function identityFromOAuth(SocialProvider $provider): SocialIdentity
    {
        $socialUser = Socialite::driver($provider->value)->user();

        return new SocialIdentity(
            provider: $provider,
            id: (string) $socialUser->getId(),
            name: $socialUser->getName() ?: ($socialUser->getNickname() ?: (string) $socialUser->getEmail()),
            email: $socialUser->getEmail(),
            emailIsVerified: match ($provider) {
                SocialProvider::Google => (bool) ($socialUser->user['email_verified'] ?? false),
                SocialProvider::Facebook => filled($socialUser->getEmail()),
                default => false,
            },
            avatarUrl: $socialUser->getAvatar(),
        );
    }
}
