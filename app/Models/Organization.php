<?php

namespace App\Models;

use App\Enums\Feature;
use App\Enums\Permission;
use App\Enums\Plan;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'allowed_email_domains', 'allow_self_registration', 'default_locale'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'plan' => 'free',
        'is_active' => true,
        'allowed_email_domains' => '[]',
        'allow_self_registration' => false,
        'default_locale' => 'en',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'plan' => Plan::class,
            'is_active' => 'boolean',
            'allowed_email_domains' => 'array',
            'allow_self_registration' => 'boolean',
        ];
    }

    /**
     * Give new organizations a unique URL slug based on their name.
     */
    protected static function booted(): void
    {
        static::creating(function (Organization $organization): void {
            if (filled($organization->slug)) {
                return;
            }

            $base = Str::slug($organization->name) ?: 'company';
            $slug = $base;

            for ($suffix = 2; static::where('slug', $slug)->exists(); $suffix++) {
                $slug = "{$base}-{$suffix}";
            }

            $organization->slug = $slug;
        });
    }

    /**
     * Use the slug in URLs such as the employee join link.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the people in the organization.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the roles defined by the organization.
     *
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Get the organization's ticket categories.
     *
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the organization's tickets.
     *
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Determine if the organization's plan includes the feature.
     */
    public function hasFeature(Feature $feature): bool
    {
        return $this->plan->includes($feature);
    }

    /**
     * Determine if the organization's plan allows the permission to be used at all.
     */
    public function allowsPermission(Permission $permission): bool
    {
        return $permission->requiredFeature() === null || $this->hasFeature($permission->requiredFeature());
    }

    /**
     * Get the number of active users who can work on tickets, which counts against the plan's agent seats.
     */
    public function agentCount(?User $except = null): int
    {
        return User::withoutGlobalScope('organization')
            ->whereBelongsTo($this)
            ->where('is_active', true)
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->whereHas('role', fn ($query) => $query
                ->withoutGlobalScope('organization')
                ->where(fn ($query) => $query
                    ->where('slug', Role::AdministratorSlug)
                    ->orWhereJsonContains('permissions', Permission::TicketsWork->value)))
            ->count();
    }

    /**
     * Determine if another agent seat can be filled, optionally ignoring a user whose seat is being reassigned.
     */
    public function hasAgentSeatAvailable(?User $except = null): bool
    {
        $limit = $this->plan->maxAgents();

        return $limit === null || $this->agentCount($except) < $limit;
    }

    /**
     * Get the plan's agent seat limit as a translated validation message.
     */
    public function agentSeatLimitMessage(): string
    {
        return __('Your :plan plan includes :count support agents. Upgrade your plan to add more.', [
            'plan' => $this->plan->label(),
            'count' => $this->plan->maxAgents(),
        ]);
    }

    /**
     * Determine if employees with the email address may join the organization on their own.
     */
    public function acceptsEmail(?string $email): bool
    {
        $domain = Str::lower(Str::after((string) $email, '@'));

        return $domain !== '' && in_array($domain, array_map(Str::lower(...), $this->allowed_email_domains), true);
    }

    /**
     * Find the active organization that auto-accepts the email address's domain.
     */
    public static function forEmailDomain(?string $email): ?self
    {
        $domain = Str::lower(Str::after((string) $email, '@'));

        return $domain === ''
            ? null
            : static::where('is_active', true)->whereJsonContains('allowed_email_domains', $domain)->first();
    }
}
