<?php

namespace App\Models;

use App\Enums\Permission;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\TicketStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\Tenancy;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'department'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use BelongsToOrganization, HasApiTokens, HasFactory, Notifiable;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'is_platform_admin' => false,
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var list<string>
     */
    protected $with = ['role'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_platform_admin' => 'boolean',
        ];
    }

    /**
     * Give new users the default role when none was chosen.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->organization_id ??= app(Tenancy::class)->id();

            if ($user->role_id === null) {
                $user->role()->associate(Role::defaultFor($user->organization_id));
            }
        });
    }

    /**
     * Get the language notifications should be sent in.
     */
    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Get the role of the user.
     *
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class)->withoutGlobalScope('organization');
    }

    /**
     * Get the Google, Facebook and Telegram accounts linked to the user.
     *
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Get the tickets submitted by the user.
     *
     * @return HasMany<Ticket, $this>
     */
    public function requestedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    /**
     * Get the tickets assigned to the user.
     *
     * @return HasMany<Ticket, $this>
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    /**
     * Determine if the user's role grants the given permission.
     */
    public function hasPermission(Permission $permission): bool
    {
        return $this->role->hasPermission($permission)
            && ($this->organization?->allowsPermission($permission) ?? false);
    }

    /**
     * Determine if the user can sign in with a password.
     */
    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    /**
     * Get the public URL of the user's profile photo, if they uploaded one.
     */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    /**
     * Determine if the user runs the platform and can manage every organization.
     */
    public function isPlatformAdmin(): bool
    {
        return $this->is_platform_admin;
    }

    /**
     * Determine if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role->isAdministrator();
    }

    /**
     * Determine if the user is IT support staff that can work on tickets.
     */
    public function isStaff(): bool
    {
        return $this->role->hasPermission(Permission::TicketsWork);
    }

    /**
     * Scope the query to active users that tickets can be assigned to.
     */
    #[Scope]
    protected function assignable(Builder $query): void
    {
        $query->where('is_active', true)->whereHas('role', fn (Builder $query) => $query
            ->where('slug', Role::AdministratorSlug)
            ->orWhereJsonContains('permissions', Permission::TicketsWork->value));
    }

    /**
     * Scope the query to include the number of active tickets assigned to each user.
     */
    #[Scope]
    protected function withOpenTicketCount(Builder $query): void
    {
        $query->withCount(['assignedTickets as open_tickets_count' => fn (Builder $query) => $query
            ->whereIn('status', TicketStatus::active())]);
    }
}
