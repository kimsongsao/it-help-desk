<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\WorkflowActionType;
use App\Enums\WorkflowField;
use App\Enums\WorkflowOperator;
use App\Enums\WorkflowTrigger;
use App\Models\Category;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWorkflowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('workflows.manage');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'stop_processing' => $this->boolean('stop_processing'),
            'is_active' => $this->boolean('is_active'),
            'conditions' => array_values((array) $this->input('conditions', [])),
            'actions' => array_values((array) $this->input('actions', [])),
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
            'trigger' => ['required', Rule::enum(WorkflowTrigger::class)],
            'match_type' => ['required', Rule::in(['all', 'any'])],
            'conditions' => ['array', 'max:20'],
            'conditions.*.field' => ['required', Rule::enum(WorkflowField::class)],
            'conditions.*.operator' => ['required', Rule::enum(WorkflowOperator::class)],
            'conditions.*.value' => ['nullable', 'string', 'max:255'],
            'actions' => ['required', 'array', 'min:1', 'max:20'],
            'actions.*.type' => ['required', Rule::enum(WorkflowActionType::class)],
            'actions.*.value' => ['nullable', 'string', 'max:2000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'stop_processing' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach ($this->input('conditions', []) as $index => $condition) {
                    if (WorkflowOperator::from($condition['operator'])->needsValue() && blank($condition['value'] ?? null)) {
                        $validator->errors()->add("conditions.{$index}.value", __('Condition :number needs a value.', ['number' => $index + 1]));
                    }
                }

                foreach ($this->input('actions', []) as $index => $action) {
                    if (! $this->isValidActionValue(WorkflowActionType::from($action['type']), $action['value'] ?? null)) {
                        $validator->errors()->add("actions.{$index}.value", __('Action :number has an invalid value.', ['number' => $index + 1]));
                    }
                }
            },
        ];
    }

    /**
     * Get the validated workflow attributes, keeping only the relevant keys of each condition and action.
     *
     * @return array<string, mixed>
     */
    public function workflowAttributes(): array
    {
        $validated = $this->validated();

        $validated['conditions'] = array_map(fn (array $condition): array => [
            'field' => $condition['field'],
            'operator' => $condition['operator'],
            'value' => WorkflowOperator::from($condition['operator'])->needsValue() ? $condition['value'] : null,
        ], $validated['conditions'] ?? []);

        $validated['actions'] = array_map(fn (array $action): array => [
            'type' => $action['type'],
            'value' => WorkflowActionType::from($action['type'])->needsValue() ? $action['value'] : null,
        ], $validated['actions']);

        $validated['position'] ??= 0;

        return $validated;
    }

    /**
     * Determine if the value is valid for the action type.
     */
    private function isValidActionValue(WorkflowActionType $type, ?string $value): bool
    {
        return match ($type) {
            WorkflowActionType::SetStatus => TicketStatus::tryFrom((string) $value) !== null,
            WorkflowActionType::SetPriority => TicketPriority::tryFrom((string) $value) !== null,
            WorkflowActionType::SetCategory => is_numeric($value) && Category::whereKey((int) $value)->exists(),
            WorkflowActionType::AssignTo => is_numeric($value) && User::assignable()->whereKey((int) $value)->exists(),
            WorkflowActionType::AssignLeastBusy => true,
            WorkflowActionType::AssignToTeam => is_numeric($value) && Team::whereKey((int) $value)->exists(),
            WorkflowActionType::AddInternalNote, WorkflowActionType::Escalate => filled($value),
        };
    }
}
