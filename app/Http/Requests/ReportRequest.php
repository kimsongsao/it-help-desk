<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('reports.view');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:'.now()->addDay()->toDateString()],
        ];
    }

    /**
     * Get the start of the reporting period. Defaults to 30 days ago.
     */
    public function from(): CarbonImmutable
    {
        $to = $this->to();
        $from = $this->date('from')?->toImmutable()->startOfDay() ?? $to->subDays(29)->startOfDay();

        return $from->max($to->subDays(365)->startOfDay());
    }

    /**
     * Get the end of the reporting period. Defaults to today.
     */
    public function to(): CarbonImmutable
    {
        return ($this->date('to')?->toImmutable() ?? CarbonImmutable::now())->endOfDay();
    }
}
