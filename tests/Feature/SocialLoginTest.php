<?php

use App\Models\SocialAccount;
use App\Models\User;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    $this->organization->update(['allowed_email_domains' => ['example.com']]);

    config([
        'services.google.client_id' => 'google-id',
        'services.google.client_secret' => 'google-secret',
        'services.facebook.client_id' => 'facebook-id',
        'services.facebook.client_secret' => 'facebook-secret',
        'services.telegram.bot_username' => 'HelpDeskBot',
        'services.telegram.bot_token' => '123456:telegram-secret',
    ]);
});

/**
 * Build the query string the Telegram login widget sends, signed with the bot token.
 *
 * @param  array<string, string|int>  $overrides
 * @return array<string, string|int>
 */
function telegramPayload(array $overrides = [], ?string $botToken = null): array
{
    $data = ['id' => 987654, 'first_name' => 'Sok', 'last_name' => 'Dara', 'username' => 'sokdara', 'auth_date' => now()->timestamp, ...$overrides];
    ksort($data);
    $checkString = collect($data)->map(fn ($value, $key) => "{$key}={$value}")->implode("\n");
    $data['hash'] = hash_hmac('sha256', $checkString, hash('sha256', $botToken ?? config('services.telegram.bot_token'), true));

    return $data;
}

test('the login page shows the configured providers only', function () {
    config(['services.facebook.client_id' => null]);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Continue with Google')
        ->assertSee('Continue with Telegram')
        ->assertDontSee('Continue with Facebook');
});

test('users are sent to google to sign in', function () {
    Socialite::fake('google');

    $this->get(route('social.redirect', 'google'))->assertRedirect();
});

test('unknown or unconfigured providers are not found', function () {
    config(['services.facebook.client_secret' => null]);

    $this->get(route('social.redirect', 'facebook'))->assertNotFound();
    $this->get('/auth/github/redirect')->assertNotFound();
});

test('a new google user gets a requester account', function () {
    Socialite::fake('google', (new SocialiteUser)->setRaw(['email_verified' => true])->map([
        'id' => 'google-123',
        'name' => 'Chan Sophea',
        'email' => 'Sophea@Example.com',
        'avatar' => 'https://example.com/avatar.png',
    ]));

    $this->get(route('social.callback', 'google'))->assertRedirect(route('dashboard'));

    $user = User::firstWhere('email', 'sophea@example.com');
    $account = $user->socialAccounts()->sole();

    expect($user->name)->toBe('Chan Sophea')
        ->and($user->role->slug)->toBe('requester')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($account->provider_id)->toBe('google-123')
        ->and($account->avatar_url)->toBe('https://example.com/avatar.png');
    $this->assertAuthenticatedAs($user);
});

test('a returning social user signs in to the same account', function () {
    $user = User::factory()->agent()->create();
    $user->socialAccounts()->create(['provider' => 'facebook', 'provider_id' => 'fb-1']);

    Socialite::fake('facebook', SocialiteUser::fake(['id' => 'fb-1', 'name' => 'Someone Else', 'email' => 'other@example.com']));

    $this->get(route('social.callback', 'facebook'))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect(User::count())->toBe(1);
});

test('a verified email links the provider to the existing account', function () {
    $user = User::factory()->create(['email' => 'dara@example.com']);
    Socialite::fake('google', (new SocialiteUser)->setRaw(['email_verified' => true])->map(['id' => 'g-9', 'name' => 'Dara', 'email' => 'dara@example.com']));

    $this->get(route('social.callback', 'google'));

    $this->assertAuthenticatedAs($user);
    expect($user->socialAccounts()->sole()->provider_id)->toBe('g-9');
});

test('an unverified email cannot take over an existing account', function () {
    User::factory()->create(['email' => 'dara@example.com']);
    Socialite::fake('google', (new SocialiteUser)->setRaw(['email_verified' => false])->map(['id' => 'g-9', 'name' => 'Dara', 'email' => 'dara@example.com']));

    $this->get(route('social.callback', 'google'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['social' => 'An account with this email already exists. Log in with your password first.']);

    $this->assertGuest();
    expect(SocialAccount::count())->toBe(0);
});

test('deactivated users cannot sign in with a social account', function () {
    $user = User::factory()->inactive()->create();
    $user->socialAccounts()->create(['provider' => 'google', 'provider_id' => 'g-1']);
    Socialite::fake('google', SocialiteUser::fake(['id' => 'g-1']));

    $this->get(route('social.callback', 'google'))->assertSessionHasErrors('social');

    $this->assertGuest();
});

test('a cancelled provider login returns to the login page with a message', function () {
    Socialite::fake('google', fn () => throw new RuntimeException('access_denied'));

    $this->get(route('social.callback', 'google'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['social' => 'Google sign in was cancelled or failed. Please try again.']);

    $this->assertGuest();
});

test('users can sign in with telegram through their company join link', function () {
    $this->organization->update(['allow_self_registration' => true]);

    $this->withSession(['joining_organization_id' => $this->organization->id])
        ->get(route('social.callback', ['provider' => 'telegram', ...telegramPayload()]))
        ->assertRedirect(route('dashboard'));

    $user = SocialAccount::where('provider', 'telegram')->where('provider_id', '987654')->sole()->user;
    expect($user->name)->toBe('Sok Dara')
        ->and($user->email)->toBeNull()
        ->and($user->organization->is($this->organization))->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

test('telegram logins with a forged or expired signature are rejected', function (Closure $payload, string $message) {
    $this->get(route('social.callback', ['provider' => 'telegram', ...$payload()]))
        ->assertSessionHasErrors(['social' => $message]);

    $this->assertGuest();
    expect(User::count())->toBe(0);
})->with([
    'signed with another bot' => [fn () => telegramPayload(botToken: '999:other-bot'), 'The Telegram login could not be verified. Please try again.'],
    'tampered user id' => [fn () => [...telegramPayload(), 'id' => 1], 'The Telegram login could not be verified. Please try again.'],
    'older than a day' => [fn () => telegramPayload(['auth_date' => now()->subDays(2)->timestamp]), 'The Telegram login has expired. Please try again.'],
]);

test('new social users without a company to join are turned away', function () {
    Socialite::fake('google', (new SocialiteUser)->setRaw(['email_verified' => true])->map(['id' => 'g-5', 'name' => 'Stranger', 'email' => 'stranger@unknown.test']));

    $this->get(route('social.callback', 'google'))
        ->assertSessionHasErrors(['social' => 'We could not find your company. Ask your administrator for your help desk join link, or create a workspace for your company.']);

    $this->assertGuest();
    expect(User::withoutGlobalScopes()->where('email', 'stranger@unknown.test')->exists())->toBeFalse();
});
