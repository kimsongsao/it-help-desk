<?php

namespace App\Http\Resources;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ticket
 */
class TicketResource extends JsonResource
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
            'reference' => $this->reference,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'attachments' => TicketAttachmentResource::collection($this->whenLoaded('ticketAttachments')),
            'requester' => UserResource::make($this->whenLoaded('requester')),
            'assignee' => UserResource::make($this->whenLoaded('assignee')),
            'team' => $this->whenLoaded('team', fn () => $this->team ? ['id' => $this->team->id, 'name' => $this->team->name] : null),
            'sla' => [
                'policy' => $this->whenLoaded('slaPolicy', fn () => $this->slaPolicy?->name),
                'first_response_due_at' => $this->first_response_due_at,
                'first_responded_at' => $this->first_responded_at,
                'first_response_status' => $this->firstResponseSlaStatus()?->value,
                'resolution_due_at' => $this->resolution_due_at,
                'resolution_status' => $this->resolutionSlaStatus()?->value,
                'paused' => $this->sla_paused_at !== null,
            ],
            'escalation_level' => $this->escalation_level,
            'escalated_at' => $this->escalated_at,
            'escalations' => TicketEscalationResource::collection($this->whenLoaded('escalations')),
            'resolved_at' => $this->resolved_at,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
