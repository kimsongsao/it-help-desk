<?php

namespace App\Http\Requests\Auth;

use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class JoinOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $this->route('organization')->id)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'timezone' => ['nullable', 'timezone:all'],
        ];
    }

    /**
     * Only accept emails the organization allows when open registration is off.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Organization $organization */
                $organization = $this->route('organization');

                if (! $organization->allow_self_registration && ! $organization->acceptsEmail($this->input('email'))) {
                    $validator->errors()->add('email', __('Use your work email address (:domains) to join :company.', [
                        'domains' => implode(', ', array_map(fn (string $domain): string => "@{$domain}", $organization->allowed_email_domains)),
                        'company' => $organization->name,
                    ]));
                }
            },
        ];
    }
}
