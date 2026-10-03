<x-layouts.guest :title="__('Join :company', ['company' => $organization->name])">
    <span class="avatar avatar-lg bg-primary-lt mb-3">{{ mb_strtoupper(mb_substr($organization->name, 0, 2)) }}</span>
    <h1 class="h1 mb-1">{{ __('Join :company', ['company' => $organization->name]) }}</h1>
    <p class="text-secondary mb-4">
        {{ __('Create your account to get help from the :company team.', ['company' => $organization->name]) }}
        @if (! $organization->allow_self_registration)
            {{ __('Use your work email (:domains).', ['domains' => implode(', ', array_map(fn ($domain) => "@{$domain}", $organization->allowed_email_domains))]) }}
        @endif
    </p>

    @error('social')
        <div class="alert alert-danger" role="alert"><i class="ti ti-alert-circle me-1"></i> {{ $message }}</div>
    @enderror

    <x-social-login action="register" />

    <form method="POST" action="{{ route('join', $organization) }}" novalidate>
        @csrf

        <div class="mb-3">
            <label class="form-label required" for="name">{{ __('Full name') }}</label>
            <div class="input-group input-group-flat">
                <span class="input-group-text"><i class="ti ti-user"></i></span>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" @class(['form-control', 'is-invalid' => $errors->has('name')])>
            </div>
            <x-field-error name="name" />
        </div>

        <div class="mb-3">
            <label class="form-label required" for="email">{{ __('Email address') }}</label>
            <div class="input-group input-group-flat">
                <span class="input-group-text"><i class="ti ti-mail"></i></span>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" @class(['form-control', 'is-invalid' => $errors->has('email')])>
            </div>
            <x-field-error name="email" />
        </div>

        <div class="mb-3">
            <label class="form-label" for="department">{{ __('Department') }} <span class="form-label-description">{{ __('Optional') }}</span></label>
            <div class="input-group input-group-flat">
                <span class="input-group-text"><i class="ti ti-building"></i></span>
                <input id="department" name="department" type="text" value="{{ old('department') }}" class="form-control">
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6 mb-3">
                <label class="form-label required" for="password">{{ __('Password') }}</label>
                <x-password-input autocomplete="new-password" />
            </div>
            <div class="col-sm-6 mb-3">
                <label class="form-label required" for="password_confirmation">{{ __('Confirm password') }}</label>
                <x-password-input name="password_confirmation" autocomplete="new-password" />
            </div>
            <x-field-error name="password" class="mt-n2 mb-3" />
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">{{ __('Create account') }} <i class="ti ti-arrow-right icon ms-1"></i></button>
    </form>

    <x-slot:footer>
        <div class="text-center text-secondary mt-4">
            {{ __('Already have an account?') }} <a href="{{ route('login') }}" class="fw-medium">{{ __('Log in') }}</a>
        </div>
    </x-slot:footer>
</x-layouts.guest>
