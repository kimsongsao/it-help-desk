<?php

namespace App\Http\Requests;

use App\Support\DisplayTimezone;
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
     * Get the start of the reporting period (the viewer's local midnight, in UTC). Defaults to 30 days ago.
     */
    public function from(): CarbonImmutable
    {
        $to = $this->to()->setTimezone($this->timezone());
        $from = $this->localDate('from')?->startOfDay() ?? $to->subDays(29)->startOfDay();

        return $from->max($to->subDays(365)->startOfDay())->utc();
    }

    /**
     * Get the end of the reporting period (the end of the viewer's local day, in UTC). Defaults to today.
     */
    public function to(): CarbonImmutable
    {
        return ($this->localDate('to') ?? CarbonImmutable::now($this->timezone()))->endOfDay()->utc();
    }

    /**
     * Get the time zone the report's days are counted in.
     */
    public function timezone(): string
    {
        return app(DisplayTimezone::class)->get();
    }

    /**
     * Read a date input as a day in the viewer's time zone.
     */
    private function localDate(string $key): ?CarbonImmutable
    {
        return $this->filled($key) ? CarbonImmutable::createFromFormat('Y-m-d', $this->input($key), $this->timezone()) : null;
    }
}
