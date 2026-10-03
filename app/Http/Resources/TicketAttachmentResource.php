<?php

namespace App\Http\Resources;

use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TicketAttachment
 */
class TicketAttachmentResource extends JsonResource
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
            'name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'is_voice_note' => $this->is_voice_note,
            'url' => route('api.v1.attachments.show', $this),
            'created_at' => $this->created_at,
        ];
    }
}
