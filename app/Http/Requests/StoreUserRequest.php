<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Support\Tenancy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('users.manage');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * Stop a new active agent from exceeding the plan's agent seats.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $role = Role::find($this->input('role_id'));
                $organization = $this->user()->organization;

                if ($role?->usesAgentSeat() && $this->boolean('is_active') && ! $organization->hasAgentSeatAvailable(except: $this->route('user'))) {
                    $validator->errors()->add('role_id', $organization->agentSeatLimitMessage());
                }
            },
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'department' => ['nullable', 'string', 'max:100'],
            'role_id' => ['required', 'integer', app(Tenancy::class)->exists('roles')],
            'is_active' => ['boolean'],
            'password' => [$this->route('user') ? 'nullable' : 'required', Password::defaults()],
        ];
    }
}
