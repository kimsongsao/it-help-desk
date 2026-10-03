<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Rules\AssignableUser;
use App\Support\Tenancy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreSlaPolicyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('sla.manage');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_hours_only' => $this->boolean('business_hours_only'),
            'escalate_on_breach' => $this->boolean('escalate_on_breach'),
            'is_active' => $this->boolean('is_active'),
            'business_days' => array_map('intval', (array) $this->input('business_days', [])),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
            'category_id' => ['nullable', 'integer', app(Tenancy::class)->exists('categories')],
            'first_response_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
            'resolution_minutes' => ['required', 'integer', 'min:1', 'max:525600', 'gte:first_response_minutes'],
            'business_hours_only' => ['boolean'],
            'business_hours_start' => ['required_if:business_hours_only,true', 'date_format:H:i'],
            'business_hours_end' => ['required_if:business_hours_only,true', 'date_format:H:i', 'after:business_hours_start'],
            'business_days' => ['array', 'required_if:business_hours_only,true'],
            'business_days.*' => ['integer', 'between:1,7', 'distinct'],
            'escalate_on_breach' => ['boolean'],
            'escalate_to_id' => ['nullable', new AssignableUser],
            'is_active' => ['boolean'],
        ];
    }
}
