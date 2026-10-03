<?php

use App\Models\Organization;
use App\Models\User;

test('organization admins can update company settings', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('organization.update'), [
            'name' => 'Acme Cambodia',
            'default_locale' => 'km',
            'timezone' => 'Asia/Bangkok',
            'allowed_email_domains' => '@Acme.com.kh, acme.com',
            'allow_self_registration' => '1',
        ])
        ->assertRedirect(route('organization.edit'));

    $this->organization->refresh();
    expect($this->organization->name)->toBe('Acme Cambodia')
        ->and($this->organization->default_locale)->toBe('km')
        ->and($this->organization->timezone)->toBe('Asia/Bangkok')
        ->and($this->organization->allowed_email_domains)->toBe(['acme.com.kh', 'acme.com'])
        ->and($this->organization->allow_self_registration)->toBeTrue();
});

test('public email providers cannot be used for automatic joining', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('organization.update'), [
            'name' => 'Acme', 'default_locale' => 'en', 'timezone' => 'UTC', 'allowed_email_domains' => 'gmail.com',
        ])
        ->assertSessionHasErrors('allowed_email_domains.0');
});

test('agents cannot change organization settings', function () {
    $this->actingAs(User::factory()->agent()->create())->get(route('organization.edit'))->assertForbidden();
});

test('employees with a company email can join through the join link', function () {
    $this->organization->update(['allowed_email_domains' => ['acme.test']]);

    $this->get(route('join', $this->organization))->assertOk()->assertSee('Test Company');

    $this->post(route('join', $this->organization), [
        'name' => 'Vanna', 'email' => 'vanna@acme.test',
        'password' => 'secret-password', 'password_confirmation' => 'secret-password',
    ])->assertRedirect(route('dashboard'));

    $user = User::firstWhere('email', 'vanna@acme.test');
    expect($user->organization->is($this->organization))->toBeTrue()
        ->and($user->role->slug)->toBe('requester');
});

test('other email domains are refused unless registration is open', function () {
    $this->organization->update(['allowed_email_domains' => ['acme.test']]);

    $this->post(route('join', $this->organization), [
        'name' => 'Outsider', 'email' => 'outsider@elsewhere.test',
        'password' => 'secret-password', 'password_confirmation' => 'secret-password',
    ])->assertSessionHasErrors('email');

    $this->organization->update(['allow_self_registration' => true]);

    $this->post(route('join', $this->organization), [
        'name' => 'Customer', 'email' => 'customer@elsewhere.test',
        'password' => 'secret-password', 'password_confirmation' => 'secret-password',
    ])->assertRedirect(route('dashboard'));
});

test('closed or suspended organizations have no join page', function () {
    $this->get(route('join', $this->organization))->assertNotFound();

    $suspended = Organization::factory()->inactive()->create(['allow_self_registration' => true]);
    $this->get(route('join', $suspended))->assertNotFound();
});
