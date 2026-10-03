<?php

namespace App\Http\Requests;

use App\Support\Tenancy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('categories.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', app(Tenancy::class)->unique('categories', 'name')->ignore($this->route('category'))],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'regex:/^ti-[a-z0-9-]+$/', 'max:50'],
            'default_team_id' => ['nullable', 'integer', app(Tenancy::class)->exists('teams')],
        ];
    }
}
