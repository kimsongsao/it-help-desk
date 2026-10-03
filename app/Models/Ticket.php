<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\SlaStatus;
use App\Enums\TicketActivityType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subject', 'description', 'status', 'priority', 'category_id', 'assignee_id', 'team_id'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'open',
        'priority' => 'medium',
        'escalation_level' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'first_response_at_risk_at' => 'datetime',
            'first_response_due_at' => 'datetime',
            'resolution_at_risk_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'first_response_breached_at' => 'datetime',
            'resolution_breached_at' => 'datetime',
            'escalation_level' => 'integer',
            'escalated_at' => 'datetime',
        ];
    }

    /**
     * Remove stored files with the ticket and keep the resolution timestamps in sync with the ticket status.
     */
    protected static function booted(): void
    {
        static::deleting(function (Ticket $ticket): void {
            $ticket->attachments->each->delete();
        });

        static::saving(function (Ticket $ticket): void {
            if (! $ticket->isDirty('status')) {
                return;
            }

            $ticket->resolved_at = match ($ticket->status) {
                TicketStatus::Resolved, TicketStatus::Closed => $ticket->resolved_at ?? now(),
                default => null,
            };

            $ticket->closed_at = $ticket->status === TicketStatus::Closed ? now() : null;
        });
    }

    /**
     * Get the user who submitted the ticket.
     *
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the support agent assigned to the ticket.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Get the support team the ticket is routed to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the category of the ticket.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the SLA policy governing the ticket.
     *
     * @return BelongsTo<SlaPolicy, $this>
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class);
    }

    /**
     * Get the comments on the ticket.
     *
     * @return HasMany<TicketComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    /**
     * Get every file attached to the ticket or its replies.
     *
     * @return HasMany<TicketAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * Get the files attached to the ticket itself rather than to a reply.
     *
     * @return HasMany<TicketAttachment, $this>
     */
    public function ticketAttachments(): HasMany
    {
        return $this->attachments()->whereNull('ticket_comment_id');
    }

    /**
     * Get the escalation history of the ticket.
     *
     * @return HasMany<TicketEscalation, $this>
     */
    public function escalations(): HasMany
    {
        return $this->hasMany(TicketEscalation::class);
    }

    /**
     * Get the activity log of the ticket.
     *
     * @return HasMany<TicketActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class);
    }

    /**
     * Get the human friendly ticket reference, e.g. "HD-00042".
     *
     * @return Attribute<string, never>
     */
    protected function reference(): Attribute
    {
        return Attribute::get(fn (): string => 'HD-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT));
    }

    /**
     * Record an entry in the ticket's activity log.
     *
     * The description is an English translation key; the replacements are translated when the entry is displayed.
     *
     * @param  array<string, string|int>  $replace
     * @param  array<string, mixed>  $properties
     */
    public function recordActivity(TicketActivityType $type, string $description, ?User $user = null, array $replace = [], array $properties = []): TicketActivity
    {
        $activity = $this->activities()->make([
            'type' => $type,
            'description' => $description,
            'properties' => array_filter([...$properties, 'replace' => $replace]) ?: null,
        ]);
        $activity->user()->associate($user);
        $activity->save();

        return $activity;
    }

    /**
     * Get the SLA status of the first response target.
     */
    public function firstResponseSlaStatus(): ?SlaStatus
    {
        return $this->slaStatusFor($this->first_response_due_at, $this->first_response_at_risk_at, $this->first_responded_at, $this->first_response_breached_at);
    }

    /**
     * Get the SLA status of the resolution target.
     */
    public function resolutionSlaStatus(): ?SlaStatus
    {
        return $this->slaStatusFor($this->resolution_due_at, $this->resolution_at_risk_at, $this->resolved_at, $this->resolution_breached_at);
    }

    /**
     * Determine the SLA status of a single target.
     */
    protected function slaStatusFor(?CarbonInterface $dueAt, ?CarbonInterface $atRiskAt, ?CarbonInterface $completedAt, ?CarbonInterface $breachedAt): ?SlaStatus
    {
        if ($dueAt === null) {
            return null;
        }

        if ($completedAt !== null) {
            return $breachedAt === null && $completedAt->lte($dueAt) ? SlaStatus::Met : SlaStatus::Breached;
        }

        if ($breachedAt !== null || ($this->sla_paused_at === null && $dueAt->isPast())) {
            return SlaStatus::Breached;
        }

        if ($this->sla_paused_at !== null) {
            return SlaStatus::Paused;
        }

        return $atRiskAt?->isPast() ? SlaStatus::AtRisk : SlaStatus::OnTrack;
    }

    /**
     * Scope the query to tickets the given user may see.
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->hasPermission(Permission::TicketsViewAll)) {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->whereBelongsTo($user, 'requester')
            ->orWhereBelongsTo($user, 'assignee')
            ->orWhereIn('team_id', $user->teams()->select('teams.id')));
    }

    /**
     * Scope the query to tickets that still require work.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::active());
    }

    /**
     * Scope the query to active tickets that have breached an SLA target.
     */
    #[Scope]
    protected function slaBreached(Builder $query): void
    {
        $query->active()->where(fn (Builder $query) => $query
            ->whereNotNull('first_response_breached_at')
            ->orWhereNotNull('resolution_breached_at'));
    }

    /**
     * Scope the query to active tickets close to breaching their resolution target.
     */
    #[Scope]
    protected function slaAtRisk(Builder $query): void
    {
        $query->active()
            ->whereNull('sla_paused_at')
            ->whereNull('resolution_breached_at')
            ->where('resolution_at_risk_at', '<=', now())
            ->where('resolution_due_at', '>', now());
    }

    /**
     * Scope the query using the filters available on the ticket list.
     *
     * @param  array{search?: ?string, status?: ?string, priority?: ?string, category?: int|string|null, assignee?: ?string, team?: int|string|null, sla?: ?string}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters, User $user): void
    {
        $query
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query->whereLike('subject', "%{$search}%")->orWhereLike('description', "%{$search}%")
            ))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->when($filters['category'] ?? null, fn (Builder $query, int|string $category) => $query->where('category_id', $category))
            ->when(($filters['assignee'] ?? null) === 'me', fn (Builder $query) => $query->whereBelongsTo($user, 'assignee'))
            ->when(($filters['assignee'] ?? null) === 'unassigned', fn (Builder $query) => $query->whereNull('assignee_id'))
            ->when(($filters['team'] ?? null) === 'mine', fn (Builder $query) => $query->whereIn('team_id', $user->teams()->select('teams.id')))
            ->when(($filters['team'] ?? null) === 'none', fn (Builder $query) => $query->whereNull('team_id'))
            ->when(is_numeric($filters['team'] ?? null), fn (Builder $query) => $query->where('team_id', (int) $filters['team']))
            ->when(($filters['sla'] ?? null) === 'breached', fn (Builder $query) => $query->slaBreached())
            ->when(($filters['sla'] ?? null) === 'at_risk', fn (Builder $query) => $query->slaAtRisk())
            ->when(($filters['sla'] ?? null) === 'escalated', fn (Builder $query) => $query->active()->where('escalation_level', '>', 0));
    }
}
