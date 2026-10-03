<?php

use App\Enums\Plan;
use App\Models\Organization;
use App\Models\User;

test('only platform admins can open the platform console', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('platform.organizations.index'))
        ->assertForbidden();
});

test('platform admins see every organization', function () {
    Organization::factory()->create(['name' => 'Mekong Bank']);
    $platformAdmin = User::factory()->admin()->create(['is_platform_admin' => true]);

    $this->actingAs($platformAdmin)
        ->get(route('platform.organizations.index'))
        ->assertOk()
        ->assertSee('Mekong Bank')
        ->assertSee('Test Company');
});

test('platform admins can change a plan and suspend an organization', function () {
    $customer = Organization::factory()->plan(Plan::Free)->create();
    $platformAdmin = User::factory()->admin()->create(['is_platform_admin' => true]);

    $this->actingAs($platformAdmin)
        ->put(route('platform.organizations.update', $customer), ['plan' => 'enterprise', 'is_active' => '0'])
        ->assertRedirect(route('platform.organizations.edit', $customer));

    $customer->refresh();
    expect($customer->plan)->toBe(Plan::Enterprise)
        ->and($customer->is_active)->toBeFalse();
});

test('platform admins cannot suspend their own organization', function () {
    $platformAdmin = User::factory()->admin()->create(['is_platform_admin' => true]);

    $this->actingAs($platformAdmin)
        ->put(route('platform.organizations.update', $this->organization), ['plan' => 'enterprise', 'is_active' => '0'])
        ->assertSessionHasErrors('is_active');

    expect($this->organization->refresh()->is_active)->toBeTrue();
});

test('platform admins can onboard an enterprise customer', function () {
    $this->actingAs(User::factory()->admin()->create(['is_platform_admin' => true]))
        ->post(route('platform.organizations.store'), [
            'company_name' => 'Angkor Hospital', 'plan' => 'enterprise', 'template' => 'it_support',
            'name' => 'Head of IT', 'email' => 'it@angkor.test', 'password' => 'secret-password',
        ])
        ->assertSessionHasNoErrors();

    $organization = Organization::firstWhere('name', 'Angkor Hospital');
    expect($organization->plan)->toBe(Plan::Enterprise)
        ->and(User::withoutGlobalScopes()->firstWhere('email', 'it@angkor.test')->organization->is($organization))->toBeTrue();
});
