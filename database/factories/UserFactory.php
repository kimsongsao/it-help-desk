<?php

namespace Database\Factories;

use App\Enums\Permission;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the user's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function admin(): static
    {
        return $this->withRole('admin');
    }

    /**
     * Indicate that the user is a support supervisor.
     */
    public function supervisor(): static
    {
        return $this->withRole('supervisor');
    }

    /**
     * Indicate that the user is a support agent.
     */
    public function agent(): static
    {
        return $this->withRole('agent');
    }

    /**
     * Give the user a custom role holding exactly the given permissions.
     *
     * @param  list<Permission>  $permissions
     */
    public function withPermissions(array $permissions): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::factory()->state(['permissions' => $permissions]),
        ]);
    }

    /**
     * Put the user in a department, creating one when none is given.
     */
    public function inDepartment(?Department $department = null): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department ?? Department::factory(),
        ]);
    }

    /**
     * Indicate that the user's account has been deactivated.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Give the user one of the built-in roles.
     */
    private function withRole(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('slug', $slug)->value('id'),
        ]);
    }
}
