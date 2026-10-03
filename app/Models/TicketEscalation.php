<?php

namespace App\Models;

use App\Enums\EscalationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['level', 'type', 'reason'])]
class TicketEscalation extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'type' => EscalationType::class,
        ];
    }

    /**
     * Get the escalated ticket.
     *
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who escalated the ticket. Null for automatic escalations.
     *
     * @return BelongsTo<User, $this>
     */
    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by_id');
    }

    /**
     * Get the user the ticket was escalated to.
     *
     * @return BelongsTo<User, $this>
     */
    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to_id');
    }
}
