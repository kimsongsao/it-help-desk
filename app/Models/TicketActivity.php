<?php

namespace App\Models;

use App\Enums\TicketActivityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'description', 'properties'])]
class TicketActivity extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => TicketActivityType::class,
            'properties' => 'array',
        ];
    }

    /**
     * Get the activity description in the current language.
     */
    public function message(): string
    {
        $replace = array_map(
            fn (mixed $value): mixed => is_string($value) ? __($value) : $value,
            $this->properties['replace'] ?? [],
        );

        return __($this->description, $replace);
    }

    /**
     * Get the ticket the activity belongs to.
     *
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who performed the activity. Null for automated activity.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
