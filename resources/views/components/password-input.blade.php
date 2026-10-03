@props(['name' => 'password', 'autocomplete' => 'current-password'])

<div class="input-group input-group-flat">
    <span class="input-group-text"><i class="ti ti-lock"></i></span>
    <input id="{{ $name }}" name="{{ $name }}" type="password" required autocomplete="{{ $autocomplete }}" {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
    <span class="input-group-text">
        <button type="button" class="link-secondary border-0 bg-transparent p-0 lh-1" data-password-toggle title="{{ __('Show password') }}" aria-label="{{ __('Show password') }}">
            <i class="ti ti-eye"></i>
        </button>
    </span>
</div>
