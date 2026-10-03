<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterval;
use Database\Factories\SlaPolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'description', 'priority', 'category_id', 'first_response_minutes', 'resolution_minutes',
    'business_hours_only', 'business_hours_start', 'business_hours_end', 'business_days',
    'escalate_on_breach', 'escalate_to_id', 'is_active',
])]
class SlaPolicy extends Model
{
    /** @use HasFactory<SlaPolicyFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'first_response_minutes' => 'integer',
            'resolution_minutes' => 'integer',
            'business_hours_only' => 'boolean',
            'business_days' => 'array',
            'escalate_on_breach' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the category the policy is limited to.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user that receives tickets escalated by the policy.
     *
     * @return BelongsTo<User, $this>
     */
    public function escalateTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalate_to_id');
    }

    /**
     * Get the tickets governed by the policy.
     *
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Scope the query to active policies that apply to the ticket, most specific first.
     */
    #[Scope]
    protected function applicableTo(Builder $query, Ticket $ticket): void
    {
        $query->where('is_active', true)
            ->where('organization_id', $ticket->organization_id)
            ->where(fn (Builder $query) => $query->whereNull('priority')->orWhere('priority', $ticket->priority))
            ->where(fn (Builder $query) => $query->whereNull('category_id')->orWhere('category_id', $ticket->category_id))
            ->orderByRaw('category_id IS NULL, priority IS NULL')
            ->orderBy('id');
    }

    /**
     * Format a number of minutes as a short duration such as "4h 30m".
     */
    public static function formatMinutes(int $minutes): string
    {
        return CarbonInterval::minutes($minutes)->cascade()->forHumans(['short' => true, 'parts' => 2]);
    }
}
