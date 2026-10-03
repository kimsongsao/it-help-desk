<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Http\Requests\Concerns\ValidatesAttachments;
use App\Models\Ticket;
use App\Support\Tenancy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    use ValidatesAttachments;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Ticket::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * A recorded voice note can replace the written description.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required_without:voice_note', 'nullable', 'string', 'max:10000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'category_id' => ['required', 'integer', app(Tenancy::class)->exists('categories')],
            ...$this->attachmentRules(),
        ];
    }

    /**
     * Get the ticket attributes from the validated input.
     *
     * @return array{subject: string, description: ?string, priority: string, category_id: int}
     */
    public function ticketAttributes(): array
    {
        return $this->safe()->only(['subject', 'description', 'priority', 'category_id']);
    }
}
