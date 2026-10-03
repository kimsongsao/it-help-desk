<?php

use App\Models\Department;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy;

test('admins can manage departments', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('departments.store'), ['name' => 'Finance'])->assertRedirect(route('departments.index'));
    $department = Department::firstWhere('name', 'Finance');

    $this->actingAs($admin)->patch(route('departments.update', $department), ['name' => 'Finance & Accounting'])->assertRedirect(route('departments.index'));
    expect($department->refresh()->name)->toBe('Finance & Accounting');

    $member = User::factory()->inDepartment($department)->create();
    $this->actingAs($admin)->delete(route('departments.destroy', $department));

    $this->assertModelMissing($department);
    expect($member->refresh()->department_id)->toBeNull();
});

test('department names are unique within an organization', function () {
    Department::factory()->create(['name' => 'Sales']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('departments.store'), ['name' => 'Sales'])
        ->assertSessionHasErrors('name');
});

test('agents cannot manage departments', function () {
    $this->actingAs(User::factory()->agent()->create())->get(route('departments.index'))->assertForbidden();
});

test('admins can put a user in a department', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->patch(route('users.update', $user), [
        'name' => $user->name, 'email' => $user->email, 'role_id' => $user->role_id, 'is_active' => '1',
        'department_id' => $department->id,
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->department->is($department))->toBeTrue();
});

test('departments of another organization cannot be chosen', function () {
    $foreign = app(Tenancy::class)->runAs(Organization::factory()->create(), fn () => Department::factory()->create());
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => $user->name, 'email' => $user->email, 'locale' => 'en', 'department_id' => $foreign->id,
    ])->assertSessionHasErrors('department_id');
});

test('people joining can pick a department of the company they join', function () {
    $this->organization->update(['allow_self_registration' => true]);
    $department = Department::factory()->create(['name' => 'Logistics']);

    $this->get(route('join', $this->organization))->assertSee('Logistics');

    $this->post(route('join', $this->organization), [
        'name' => 'Rith', 'email' => 'rith@example.com', 'department_id' => $department->id,
        'password' => 'secret-password', 'password_confirmation' => 'secret-password',
    ])->assertRedirect(route('dashboard'));

    expect(User::firstWhere('email', 'rith@example.com')->department->is($department))->toBeTrue();
});
