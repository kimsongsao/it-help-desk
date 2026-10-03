<?php

namespace App\Http\Requests\Profile;

use App\Support\Tenancy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
     * Users who signed up with Telegram may leave the email empty until they add one.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                $this->user()->email === null ? 'nullable' : 'required',
                'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->user()),
            ],
            'department_id' => ['nullable', 'integer', app(Tenancy::class)->exists('departments')],
            'locale' => ['required', Rule::in(array_keys(config('helpdesk.locales')))],
            'timezone' => ['nullable', 'timezone:all'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=6000,max_height=6000'],
        ];
    }

    /**
     * Get the attribute names used in validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['avatar' => __('profile photo'), 'locale' => __('language')];
    }
}
