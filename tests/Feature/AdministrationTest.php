<?php

use App\Models\Category;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;

test('non admins cannot access administration pages', function (string $routeName) {
    $this->actingAs(User::factory()->agent()->create())
        ->get(route($routeName))
        ->assertForbidden();
})->with(['categories.index', 'categories.create', 'users.index']);

test('admins can view administration pages', function (string $routeName) {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route($routeName))
        ->assertOk();
})->with(['categories.index', 'categories.create', 'users.index']);

test('admins can create, update and delete categories', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('categories.store'), ['name' => 'Telephony', 'description' => 'Desk phones'])
        ->assertRedirect(route('categories.index'));

    $category = Category::sole();
    expect($category->name)->toBe('Telephony');

    $this->actingAs($admin)
        ->patch(route('categories.update', $category), ['name' => 'Phones'])
        ->assertRedirect(route('categories.index'));
    expect($category->refresh()->name)->toBe('Phones');

    $this->actingAs($admin)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));
    $this->assertModelMissing($category);
});

test('category names must be unique', function () {
    Category::factory()->create(['name' => 'Hardware']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('categories.store'), ['name' => 'Hardware'])
        ->assertSessionHasErrors('name');
});

test('deleting a category keeps its tickets', function () {
    $ticket = Ticket::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('categories.destroy', $ticket->category));

    expect($ticket->refresh()->category_id)->toBeNull();
});

test('admins can change the role of another user', function () {
    $user = User::factory()->create();
    $agentRole = Role::firstWhere('slug', 'agent');

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $agentRole->id,
            'is_active' => '1',
        ])
        ->assertRedirect(route('users.index'));

    expect($user->refresh()->role->is($agentRole))->toBeTrue()
        ->and($user->isStaff())->toBeTrue();
});

test('admins cannot change their own role or deactivate themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role_id' => Role::firstWhere('slug', 'requester')->id,
            'is_active' => '0',
        ])
        ->assertSessionHasErrors(['role_id', 'is_active']);

    expect($admin->refresh()->isAdmin())->toBeTrue()
        ->and($admin->is_active)->toBeTrue();
});

test('agents cannot manage users', function () {
    $agent = User::factory()->agent()->create();

    $this->actingAs($agent)
        ->patch(route('users.update', $agent), [
            'name' => $agent->name,
            'email' => $agent->email,
            'role_id' => Role::firstWhere('slug', 'admin')->id,
            'is_active' => '1',
        ])
        ->assertForbidden();

    expect($agent->refresh()->isAdmin())->toBeFalse();
});

test('admins can create users with a role', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), [
            'name' => 'New Agent',
            'email' => 'new.agent@example.com',
            'role_id' => Role::firstWhere('slug', 'agent')->id,
            'password' => 'secret-password',
            'is_active' => '1',
        ])
        ->assertRedirect(route('users.index'));

    expect(User::firstWhere('email', 'new.agent@example.com')->role->slug)->toBe('agent');
});

test('deactivating a user revokes their api tokens', function () {
    $user = User::factory()->create();
    $user->createToken('script');

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'is_active' => '0',
        ]);

    expect($user->refresh()->is_active)->toBeFalse()
        ->and($user->tokens()->count())->toBe(0);
});
