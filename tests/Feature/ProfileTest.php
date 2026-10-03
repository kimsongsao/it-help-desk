<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

test('users can view their profile', function () {
    $user = User::factory()->create(['name' => 'Sok Dara']);

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Sok Dara')
        ->assertSee('Change password');
});

test('guests cannot open the profile', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});

test('users can update their details and language', function () {
    $user = User::factory()->create();
    $finance = Department::factory()->create(['name' => 'Finance']);

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => 'Chan Sophea',
        'email' => 'sophea@example.com',
        'department_id' => $finance->id,
        'locale' => 'km',
    ])->assertRedirect(route('profile.edit'))->assertSessionHas('locale', 'km');

    $user->refresh();
    expect($user->name)->toBe('Chan Sophea')
        ->and($user->email)->toBe('sophea@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->department->is($finance))->toBeTrue()
        ->and($user->locale)->toBe('km');
});

test('profile updates cannot change role, organization or platform access', function () {
    $user = User::factory()->create();
    $originalRole = $user->role_id;

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => $user->name, 'email' => $user->email, 'locale' => 'en',
        'role_id' => User::factory()->admin()->create()->role_id,
        'organization_id' => 999,
        'is_platform_admin' => '1',
        'is_active' => '0',
    ]);

    $user->refresh();
    expect($user->role_id)->toBe($originalRole)
        ->and($user->organization_id)->toBe($this->organization->id)
        ->and($user->isPlatformAdmin())->toBeFalse()
        ->and($user->is_active)->toBeTrue();
});

test('the email must stay unique', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs(User::factory()->create())
        ->put(route('profile.update'), ['name' => 'Someone', 'email' => 'taken@example.com', 'locale' => 'en'])
        ->assertSessionHasErrors('email');
});

test('users can upload, replace and remove a profile photo', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $profile = ['name' => $user->name, 'email' => $user->email, 'locale' => 'en'];

    $this->actingAs($user)->put(route('profile.update'), [...$profile, 'avatar' => UploadedFile::fake()->image('me.png', 800, 600)]);
    $firstPath = $user->refresh()->avatar_path;

    Storage::disk('public')->assertExists($firstPath);
    expect($firstPath)->toEndWith('.webp')
        ->and(getimagesizefromstring(Storage::disk('public')->get($firstPath))[0])->toBe(256);

    $this->actingAs($user)->put(route('profile.update'), [...$profile, 'avatar' => UploadedFile::fake()->image('new.jpg', 300, 300)]);
    Storage::disk('public')->assertMissing($firstPath);

    $this->actingAs($user)->delete(route('profile.avatar.destroy'));
    Storage::disk('public')->assertMissing($user->avatar_path);
    expect($user->refresh()->avatar_path)->toBeNull();
});

test('profile photos must be images', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => $user->name, 'email' => $user->email, 'locale' => 'en',
        'avatar' => UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml'),
    ])->assertSessionHasErrors('avatar');

    expect($user->refresh()->avatar_path)->toBeNull();
});

test('changing the password requires the current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.password.update'), [
        'current_password' => 'wrong-password', 'password' => 'new-secret-password', 'password_confirmation' => 'new-secret-password',
    ])->assertSessionHasErrorsIn('password', 'current_password');

    $this->actingAs($user)->put(route('profile.password.update'), [
        'current_password' => 'password', 'password' => 'new-secret-password', 'password_confirmation' => 'new-secret-password',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('new-secret-password', $user->refresh()->password))->toBeTrue();
});

test('social sign-up users can set a first password without a current one', function () {
    $user = User::factory()->create(['password' => null]);
    $user->socialAccounts()->create(['provider' => 'google', 'provider_id' => 'g-1']);

    $this->actingAs($user)->get(route('profile.edit'))->assertSee('Set a password');

    $this->actingAs($user)->put(route('profile.password.update'), [
        'password' => 'first-secret-password', 'password_confirmation' => 'first-secret-password',
    ])->assertSessionHasNoErrors();

    $this->post(route('logout'));
    $this->post(route('login'), ['email' => $user->email, 'password' => 'first-secret-password'])
        ->assertRedirect(route('dashboard'));
});

test('users without a password cannot log in with an empty one', function () {
    $user = User::factory()->create(['password' => null]);

    $this->post(route('login'), ['email' => $user->email, 'password' => ''])->assertSessionHasErrors();
    $this->assertGuest();
});

test('a social account can be disconnected when another sign-in remains', function () {
    $user = User::factory()->create();
    $account = $user->socialAccounts()->create(['provider' => 'google', 'provider_id' => 'g-1']);

    $this->actingAs($user)->delete(route('profile.social-accounts.destroy', $account))->assertSessionHasNoErrors();

    $this->assertModelMissing($account);
});

test('the last way to sign in cannot be disconnected', function () {
    $user = User::factory()->create(['password' => null]);
    $account = $user->socialAccounts()->create(['provider' => 'telegram', 'provider_id' => 't-1']);

    $this->actingAs($user)->delete(route('profile.social-accounts.destroy', $account))->assertSessionHasErrors('social');

    $this->assertModelExists($account);
});

test('users cannot disconnect the social accounts of others', function () {
    $account = User::factory()->create()->socialAccounts()->create(['provider' => 'google', 'provider_id' => 'g-2']);

    $this->actingAs(User::factory()->create())
        ->delete(route('profile.social-accounts.destroy', $account))
        ->assertNotFound();

    $this->assertModelExists($account);
});
