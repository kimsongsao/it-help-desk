<?php

use App\Enums\Plan;
use App\Models\User;

test('the login page renders', function () {
    $this->get(route('login'))->assertOk()->assertSee('Log in');
});

test('users can log in with valid credentials', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('users cannot log in with an invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a company can sign up for its own help desk', function () {
    $this->post(route('register'), [
        'company_name' => 'Mekong Logistics',
        'template' => 'customer_service',
        'name' => 'Jane Doe',
        'email' => 'jane@mekong.test',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertRedirect(route('organization.edit'));

    $owner = User::withoutGlobalScopes()->firstWhere('email', 'jane@mekong.test');
    $organization = $owner->organization;

    expect($organization->name)->toBe('Mekong Logistics')
        ->and($organization->plan)->toBe(Plan::Free)
        ->and($owner->isAdmin())->toBeTrue()
        ->and($organization->categories()->withoutGlobalScopes()->pluck('name'))->toContain('Returns & refunds')
        ->and($organization->roles()->withoutGlobalScopes()->count())->toBe(4);
    $this->assertAuthenticatedAs($owner);
});

test('company sign up is validated', function () {
    $this->post(route('register'), ['template' => 'space_station'])
        ->assertSessionHasErrors(['company_name', 'template', 'name', 'email', 'password']);
});

test('signing up cannot choose a role or plan', function () {
    $this->post(route('register'), [
        'company_name' => 'Sneaky Co',
        'template' => 'general',
        'name' => 'Mallory',
        'email' => 'mallory@example.com',
        'plan' => 'enterprise',
        'is_platform_admin' => '1',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $user = User::withoutGlobalScopes()->firstWhere('email', 'mallory@example.com');
    expect($user->organization->plan)->toBe(Plan::Free)
        ->and($user->isPlatformAdmin())->toBeFalse();
});

test('deactivated users cannot log in', function () {
    $user = User::factory()->inactive()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('deactivated users are signed out on their next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('users can log out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
