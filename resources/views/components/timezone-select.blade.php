@props(['name' => 'timezone', 'selected' => null, 'default' => null])

@php
    $groups = collect(DateTimeZone::listIdentifiers())
        ->groupBy(fn (string $timezone) => str_contains($timezone, '/') ? str($timezone)->before('/')->toString() : 'Other');
    $offset = fn (string $timezone) => now($timezone)->format('P');
@endphp

<select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
    @if ($default)
        <option value="">{{ __('Same as company (:timezone)', ['timezone' => $default]) }}</option>
    @endif
    @foreach ($groups as $region => $timezones)
        <optgroup label="{{ $region }}">
            @foreach ($timezones as $timezone)
                <option value="{{ $timezone }}" @selected($selected === $timezone)>{{ str_replace('_', ' ', $timezone) }} (UTC{{ $offset($timezone) }})</option>
            @endforeach
        </optgroup>
    @endforeach
</select>
