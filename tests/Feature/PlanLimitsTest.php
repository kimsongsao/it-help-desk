<?php

use App\Enums\Permission;
use App\Enums\Plan;
use App\Models\Role;
use App\Models\User;

test('free organizations cannot use paid features', function (string $routeName) {
    $this->organization->forceFill(['plan' => Plan::Free])->save();

    $this->actingAs(User::factory()->admin()->create())->get(route($routeName))->assertForbidden();
})->with(['reports.index', 'sla-policies.index', 'workflows.index']);

test('upgrading the plan unlocks features', function () {
    $this->organization->forceFill(['plan' => Plan::Free])->save();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('reports.index'))->assertForbidden();

    $this->organization->forceFill(['plan' => Plan::Business])->save();

    $this->actingAs($admin->fresh())->get(route('reports.index'))->assertOk();
});

test('free organizations cannot use the api', function () {
    $this->organization->forceFill(['plan' => Plan::Free])->save();
    $user = User::factory()->create();

    $this->postJson(route('api.v1.auth.token.store'), ['email' => $user->email, 'password' => 'password', 'device_name' => 'cli'])
        ->assertForbidden();
});

test('the navigation hides features outside the plan', function () {
    $this->organization->forceFill(['plan' => Plan::Free])->save();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertDontSee(route('reports.index'))
        ->assertDontSee(route('workflows.index'))
        ->assertSee(route('categories.index'));
});

test('adding an agent beyond the plan seats is refused', function () {
    $this->organization->forceFill(['plan' => Plan::Free])->save();
    $admin = User::factory()->admin()->create();
    User::factory()->agent()->count(2)->create();
    $agentRole = Role::firstWhere('slug', 'agent');

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Fourth Agent', 'email' => 'fourth@example.com', 'role_id' => $agentRole->id,
        'password' => 'secret-password', 'is_active' => '1',
    ])->assertSessionHasErrors(['role_id' => 'Your Free plan includes 3 support agents. Upgrade your plan to add more.']);

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'New Requester', 'email' => 'requester@example.com', 'role_id' => Role::firstWhere('slug', 'requester')->id,
        'password' => 'secret-password', 'is_active' => '1',
    ])->assertSessionHasNoErrors();
});

test('editing an existing agent does not need a free seat', function () {
    $this->organization->forceFill(['plan' => Plan::Free])->save();
    $admin = User::factory()->admin()->create();
    $agents = User::factory()->agent()->count(2)->create();

    $this->actingAs($admin)->patch(route('users.update', $agents->first()), [
        'name' => 'Renamed Agent', 'email' => $agents->first()->email,
        'role_id' => $agents->first()->role_id, 'is_active' => '1',
    ])->assertSessionHasNoErrors();
});

test('a role cannot turn its members into agents beyond the plan seats', function () {
    $this->organization->forceFill(['plan' => Plan::Free])->save();
    $admin = User::factory()->admin()->create();
    User::factory()->agent()->create();
    $requesterRole = Role::firstWhere('slug', 'requester');
    User::factory()->count(2)->create();

    $this->actingAs($admin)->patch(route('roles.update', $requesterRole), [
        'name' => $requesterRole->name,
        'permissions' => [Permission::TicketsCreate->value, Permission::TicketsWork->value],
    ])->assertSessionHasErrors('permissions');

    expect($requesterRole->refresh()->usesAgentSeat())->toBeFalse();
});

test('organization admins can see their plan and usage', function () {
    $this->organization->forceFill(['plan' => Plan::Business])->save();
    User::factory()->agent()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('organization.edit'))
        ->assertOk()
        ->assertSee('Business')
        ->assertSee('2 / 25');
});
