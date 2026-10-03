<?php

use App\Enums\Permission;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;

test('the administrator role holds every permission', function () {
    $admin = User::factory()->admin()->create();

    foreach (Permission::cases() as $permission) {
        expect($admin->can($permission->value))->toBeTrue();
    }
});

test('requesters only hold the permissions of their role', function () {
    $requester = User::factory()->create();

    expect($requester->can('tickets.create'))->toBeTrue()
        ->and($requester->can('tickets.view_all'))->toBeFalse()
        ->and($requester->can('reports.view'))->toBeFalse();
});

test('a custom role grants access to exactly its permissions', function () {
    $analyst = User::factory()->withPermissions([Permission::ReportsView])->create();

    $this->actingAs($analyst)->get(route('reports.index'))->assertOk();
    $this->actingAs($analyst)->get(route('sla-policies.index'))->assertForbidden();
    $this->actingAs($analyst)->get(route('tickets.create'))->assertForbidden();
});

test('changing role permissions takes effect immediately', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)->get(route('reports.index'))->assertForbidden();

    $role = Role::firstWhere('slug', 'agent');
    $role->update(['permissions' => [...$role->permissions, Permission::ReportsView]]);

    $this->actingAs($agent->fresh())->get(route('reports.index'))->assertOk();
});

test('staff without the view all permission only see tickets assigned to them', function () {
    $agent = User::factory()->withPermissions([Permission::TicketsWork, Permission::TicketsManage])->create();
    $assigned = Ticket::factory()->assigned($agent)->create();
    $other = Ticket::factory()->create();

    $this->actingAs($agent)
        ->get(route('tickets.index'))
        ->assertSee($assigned->subject)
        ->assertDontSee($other->subject);

    $this->actingAs($agent)->get(route('tickets.show', $other))->assertForbidden();
    $this->actingAs($agent)->get(route('tickets.show', $assigned))->assertOk();
});

test('admins can create and edit roles', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('roles.store'), [
            'name' => 'Service Desk Lead',
            'description' => 'Runs the desk',
            'permissions' => [Permission::TicketsViewAll->value, Permission::ReportsView->value],
        ])
        ->assertRedirect(route('roles.index'));

    $role = Role::firstWhere('slug', 'service-desk-lead');
    expect($role->permissions->all())->toBe([Permission::TicketsViewAll, Permission::ReportsView]);

    $this->actingAs($admin)
        ->patch(route('roles.update', $role), ['name' => 'Desk Lead', 'permissions' => [Permission::ReportsView->value]])
        ->assertRedirect(route('roles.index'));

    expect($role->refresh()->name)->toBe('Desk Lead')
        ->and($role->permissions->all())->toBe([Permission::ReportsView]);
});

test('role permissions are validated', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('roles.store'), ['name' => 'Broken', 'permissions' => ['tickets.fly']])
        ->assertSessionHasErrors('permissions.0');
});

test('built in roles and roles in use cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    $custom = Role::factory()->create();
    User::factory()->create(['role_id' => $custom->id]);

    $this->actingAs($admin)->delete(route('roles.destroy', Role::firstWhere('slug', 'agent')))->assertSessionHasErrors('role');
    $this->actingAs($admin)->delete(route('roles.destroy', $custom))->assertSessionHasErrors('role');

    expect(Role::whereKey($custom->id)->exists())->toBeTrue();

    $unused = Role::factory()->create();
    $this->actingAs($admin)->delete(route('roles.destroy', $unused))->assertRedirect(route('roles.index'));
    $this->assertModelMissing($unused);
});

test('only users with the roles permission can manage roles', function () {
    $this->actingAs(User::factory()->supervisor()->create())
        ->get(route('roles.index'))
        ->assertForbidden();
});
