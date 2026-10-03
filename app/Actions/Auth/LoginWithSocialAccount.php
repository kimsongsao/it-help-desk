<?php

namespace App\Actions\Auth;

use App\Exceptions\SocialLoginException;
use App\Models\Organization;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\SocialIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoginWithSocialAccount
{
    /**
     * Find or create the user for a social identity.
     *
     * An identity that was linked before signs in as its user. Otherwise a verified email links the identity
     * to the existing account with that email, and anyone else gets a new requester account.
     *
     * @throws SocialLoginException
     */
    public function handle(SocialIdentity $identity, ?Organization $joining = null): User
    {
        $user = DB::transaction(function () use ($identity, $joining): User {
            $account = SocialAccount::query()
                ->where('provider', $identity->provider)
                ->where('provider_id', $identity->id)
                ->first();

            $user = $account?->user ?? $this->findOrCreateUser($identity, $joining);

            $account ??= $user->socialAccounts()->make([
                'provider' => $identity->provider,
                'provider_id' => $identity->id,
            ]);
            $account->fill(['avatar_url' => $identity->avatarUrl, 'last_login_at' => now()])->save();

            return $user;
        });

        if (! $user->is_active) {
            throw new SocialLoginException(__('Your account has been deactivated.'));
        }

        if (! $user->organization->is_active) {
            throw new SocialLoginException(__('Your organization has been suspended.'));
        }

        return $user;
    }

    /**
     * Link the identity to the account with the same verified email, or register a new requester in the
     * company being joined (or the company that auto-accepts the email's domain).
     *
     * @throws SocialLoginException
     */
    private function findOrCreateUser(SocialIdentity $identity, ?Organization $joining): User
    {
        $existing = $identity->email
            ? User::withoutGlobalScope('organization')->where('email', Str::lower($identity->email))->first()
            : null;

        if ($existing !== null) {
            if (! $identity->emailIsVerified) {
                throw new SocialLoginException(__('An account with this email already exists. Log in with your password first.'));
            }

            if ($existing->socialAccounts()->where('provider', $identity->provider)->exists()) {
                throw new SocialLoginException(__('Your account is already linked to a different :provider account.', ['provider' => $identity->provider->label()]));
            }

            return $existing;
        }

        $organization = $this->organizationToJoin($identity, $joining);

        $user = new User([
            'name' => $identity->name,
            'email' => $identity->email ? Str::lower($identity->email) : null,
        ]);
        $user->organization()->associate($organization);
        $user->email_verified_at = $identity->email && $identity->emailIsVerified ? now() : null;
        $user->locale = app()->getLocale();
        $user->save();

        return $user;
    }

    /**
     * Pick the company a new person signs up to: the one whose join link they used, or the one that
     * auto-accepts their verified email's domain.
     *
     * @throws SocialLoginException
     */
    private function organizationToJoin(SocialIdentity $identity, ?Organization $joining): Organization
    {
        $verifiedEmail = $identity->emailIsVerified ? $identity->email : null;

        if ($joining !== null && $joining->is_active && ($joining->allow_self_registration || $joining->acceptsEmail($verifiedEmail))) {
            return $joining;
        }

        return Organization::forEmailDomain($verifiedEmail)
            ?? throw new SocialLoginException(__('We could not find your company. Ask your administrator for your help desk join link, or create a workspace for your company.'));
    }
}
