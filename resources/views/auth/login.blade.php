<x-layouts.guest :title="__('Log in')">
    <h1 class="h1 mb-1">{{ __('Welcome back') }}</h1>
    <p class="text-secondary mb-4">{{ __('Log in to submit and track your requests.') }}</p>

    @error('social')
        <div class="alert alert-danger" role="alert"><i class="ti ti-alert-circle me-1"></i> {{ $message }}</div>
    @enderror

    <x-social-login />

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label class="form-label" for="email">{{ __('Email address') }}</label>
            <div class="input-group input-group-flat">
                <span class="input-group-text"><i class="ti ti-mail"></i></span>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" @class(['form-control', 'is-invalid' => $errors->has('email')]) placeholder="you@company.com">
            </div>
            <x-field-error name="email" />
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">{{ __('Password') }}</label>
            <x-password-input />
            <x-field-error name="password" />
        </div>

        <label class="form-check mb-4">
            <input type="checkbox" name="remember" class="form-check-input">
            <span class="form-check-label">{{ __('Remember me on this device') }}</span>
        </label>

        <button type="submit" class="btn btn-primary btn-lg w-100">{{ __('Log in') }} <i class="ti ti-arrow-right icon ms-1"></i></button>
    </form>

    <x-slot:footer>
        <div class="text-center text-secondary mt-4">
            {{ __('New to :app?', ['app' => config('app.name')]) }} <a href="{{ route('register') }}" class="fw-medium">{{ __('Create a help desk for your company') }}</a>
        </div>
    </x-slot:footer>
</x-layouts.guest>
