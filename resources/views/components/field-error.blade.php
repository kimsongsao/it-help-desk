@props(['name'])

@if ($errors->has($name))
    <div {{ $attributes->class('invalid-feedback d-block') }}>{{ $errors->first($name) }}</div>
@endif
