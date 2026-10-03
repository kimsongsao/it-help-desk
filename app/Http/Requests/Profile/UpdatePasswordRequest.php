<?php

namespace App\Http\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
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
     * The current password is only needed when the user already has one.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => $this->user()->hasPassword() ? ['required', 'current_password'] : ['prohibited'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Get the error bag for the password form, so its errors do not mix with the profile form.
     */
    protected function getValidatorInstance(): \Illuminate\Contracts\Validation\Validator
    {
        $this->errorBag = 'password';

        return parent::getValidatorInstance();
    }
}
