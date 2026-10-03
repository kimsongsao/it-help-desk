<?php

namespace App\Models;

use App\Enums\Permission;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'permissions'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * The slug of the role that always holds every permission.
     */
    public const string AdministratorSlug = 'admin';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'permissions' => AsEnumCollection::of(Permission::class),
            'is_system' => 'boolean',
        ];
    }

    /**
     * Get the users with the role.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the role an organization gives new users by default.
     */
    public static function defaultFor(Organization|int $organization): self
    {
        return static::withoutGlobalScope('organization')
            ->where('organization_id', $organization instanceof Organization ? $organization->id : $organization)
            ->where('slug', config('helpdesk.default_role'))
            ->firstOrFail();
    }

    /**
     * Determine if the role is the built-in administrator role.
     */
    public function isAdministrator(): bool
    {
        return $this->slug === self::AdministratorSlug;
    }

    /**
     * Determine if users with the role take up a support agent seat.
     */
    public function usesAgentSeat(): bool
    {
        return $this->hasPermission(Permission::TicketsWork);
    }

    /**
     * Determine if the role grants the given permission.
     */
    public function hasPermission(Permission $permission): bool
    {
        return $this->isAdministrator() || $this->permissions->contains($permission);
    }
}
