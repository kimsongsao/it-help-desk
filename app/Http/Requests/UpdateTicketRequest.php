<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Rules\AssignableUser;
use App\Support\Tenancy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $this->user()->can('update', $ticket) || $this->user()->can('assign', $ticket);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Fields the user lacks permission for are prohibited.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Ticket $ticket */
        $ticket = $this->route('ticket');
        $canUpdate = $this->user()->can('update', $ticket);

        return [
            'status' => $canUpdate ? ['sometimes', 'required', Rule::enum(TicketStatus::class), $this->allowedTransition($ticket)] : ['prohibited'],
            'priority' => $canUpdate ? ['sometimes', 'required', Rule::enum(TicketPriority::class)] : ['prohibited'],
            'category_id' => $canUpdate ? ['sometimes', 'required', 'integer', app(Tenancy::class)->exists('categories')] : ['prohibited'],
            'assignee_id' => $this->user()->can('assign', $ticket) ? ['sometimes', 'nullable', new AssignableUser] : ['prohibited'],
        ];
    }

    /**
     * Ensure the ticket's workflow allows moving to the requested status.
     */
    private function allowedTransition(Ticket $ticket): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ticket): void {
            $status = TicketStatus::tryFrom((string) $value);

            if ($status !== null && ! $ticket->status->canTransitionTo($status)) {
                $fail(__('A :from ticket cannot be moved to :to.', ['from' => $ticket->status->label(), 'to' => $status->label()]));
            }
        };
    }
}
