<?php

namespace App\Http\Resources;

use App\Models\TicketEscalation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TicketEscalation
 */
class TicketEscalationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'type' => $this->type->value,
            'reason' => $this->reason,
            'escalated_by' => UserResource::make($this->whenLoaded('escalatedBy')),
            'escalated_to' => UserResource::make($this->whenLoaded('escalatedTo')),
            'created_at' => $this->created_at,
        ];
    }
}
