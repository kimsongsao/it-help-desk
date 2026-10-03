<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateOrganizationSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('organization.manage');
    }

    /**
     * Turn the comma separated domain list into a clean array.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'allow_self_registration' => $this->boolean('allow_self_registration'),
            'allowed_email_domains' => collect(preg_split('/[\s,;]+/', (string) $this->input('allowed_email_domains')))
                ->map(fn (string $domain): string => Str::of($domain)->lower()->ltrim('@')->trim()->toString())
                ->filter()
                ->unique()
                ->values()
                ->all(),
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
            'name' => ['required', 'string', 'max:255'],
            'default_locale' => ['required', Rule::in(array_keys(config('helpdesk.locales')))],
            'timezone' => ['required', 'timezone:all'],
            'allow_self_registration' => ['boolean'],
            'allowed_email_domains' => ['array', 'max:20'],
            'allowed_email_domains.*' => ['string', 'max:255', 'regex:/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)+$/', Rule::notIn(config('helpdesk.public_email_domains'))],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'allowed_email_domains.*.not_in' => __('Public email providers such as :domain cannot be used for automatic joining.', ['domain' => ':input']),
            'allowed_email_domains.*.regex' => __(':input is not a valid domain.'),
        ];
    }
}
