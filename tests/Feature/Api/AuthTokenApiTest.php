<?php

use App\Enums\Permission;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('users can exchange their credentials for a token', function () {
    $user = User::factory()->create();

    $response = $this->postJson(route('api.v1.auth.token.store'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'cli',
    ]);

    $response->assertCreated()->assertJsonStructure(['token', 'token_type']);
    expect($user->tokens()->sole()->name)->toBe('cli');

    $this->withToken($response->json('token'))
        ->getJson(route('api.v1.me'))
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.role.slug', 'requester')
        ->assertJsonPath('data.permissions', [Permission::TicketsCreate->value, Permission::ApiAccess->value]);
});

test('invalid or deactivated credentials do not get a token', function () {
    $user = User::factory()->create();
    $inactive = User::factory()->inactive()->create();

    $this->postJson(route('api.v1.auth.token.store'), ['email' => $user->email, 'password' => 'wrong', 'device_name' => 'cli'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->postJson(route('api.v1.auth.token.store'), ['email' => $inactive->email, 'password' => 'password', 'device_name' => 'cli'])
        ->assertUnprocessable();
});

test('users without api access cannot get a token or call the api', function () {
    $user = User::factory()->withPermissions([Permission::TicketsCreate])->create();

    $this->postJson(route('api.v1.auth.token.store'), ['email' => $user->email, 'password' => 'password', 'device_name' => 'cli'])
        ->assertForbidden();

    Sanctum::actingAs($user);
    $this->getJson(route('api.v1.tickets.index'))->assertForbidden();
});

test('the api requires authentication', function () {
    $this->getJson(route('api.v1.tickets.index'))->assertUnauthorized();
});

test('deactivated users cannot use existing tokens', function () {
    Sanctum::actingAs(User::factory()->inactive()->create());

    $this->getJson(route('api.v1.me'))->assertForbidden();
});

test('users can revoke the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('cli')->plainTextToken;

    $this->withToken($token)->deleteJson(route('api.v1.auth.token.destroy'))->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});

test('users can manage api tokens in the web app', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('api-tokens.store'), ['name' => 'Laptop script'])
        ->assertRedirect(route('api-tokens.index'))
        ->assertSessionHas('plainTextToken');

    $token = $user->tokens()->sole();
    $this->actingAs($user)->get(route('api-tokens.index'))->assertOk()->assertSee('Laptop script');

    $this->actingAs($user)->delete(route('api-tokens.destroy', $token->id))->assertRedirect(route('api-tokens.index'));
    expect($user->tokens()->count())->toBe(0);
});
