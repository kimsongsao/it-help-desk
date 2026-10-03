<?php

namespace App\Http\Requests;

use App\Models\Team;
use App\Rules\AssignableUser;
use App\Support\Tenancy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('teams.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', app(Tenancy::class)->unique('teams', 'name')->ignore($this->route('team'))],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['required', Rule::in(Team::Colors)],
            'members' => ['array'],
            'members.*' => ['integer', 'distinct', new AssignableUser],
            'categories' => ['array'],
            'categories.*' => ['integer', 'distinct', app(Tenancy::class)->exists('categories')],
        ];
    }
}
