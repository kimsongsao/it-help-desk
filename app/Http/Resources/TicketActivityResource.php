<?php

namespace App\Http\Resources;

use App\Models\TicketActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TicketActivity
 */
class TicketActivityResource extends JsonResource
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
            'type' => $this->type->value,
            'description' => $this->message(),
            'user' => UserResource::make($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}
