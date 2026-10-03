<x-layouts.guest :title="__('Create your workspace')">
    <h1 class="h1 mb-1">{{ __('Create your help desk') }}</h1>
    <p class="text-secondary mb-4">{{ __('Free for small teams. Set up in under a minute, no credit card needed.') }}</p>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf
        <input type="hidden" name="timezone" data-browser-timezone>

        <div class="mb-3">
            <label class="form-label required" for="company_name">{{ __('Company or team name') }}</label>
            <div class="input-group input-group-flat">
                <span class="input-group-text"><i class="ti ti-building"></i></span>
                <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required autofocus autocomplete="organization" @class(['form-control', 'is-invalid' => $errors->has('company_name')]) placeholder="{{ __('e.g. Acme Co., Ltd.') }}">
            </div>
            <x-field-error name="company_name" />
        </div>

        <div class="mb-3">
            <div class="form-label required">{{ __('What will you use it for?') }}</div>
            <div class="row g-2">
                @foreach (App\Enums\HelpDeskTemplate::cases() as $template)
                    <div class="col-6 col-sm-4">
                        <label class="form-selectgroup-item category-tile w-100 h-100">
                            <input type="radio" name="template" value="{{ $template->value }}" class="form-selectgroup-input" @checked(old('template', App\Enums\HelpDeskTemplate::General->value) === $template->value)>
                            <span class="form-selectgroup-label">
                                <i class="ti {{ $template->icon() }}"></i>
                                <span class="small fw-medium">{{ $template->label() }}</span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
            <div class="form-hint">{{ __('This only sets your starting categories. You can change them any time.') }}</div>
            <x-field-error name="template" />
        </div>

        <div class="hr-text my-4 text-secondary">{{ __('Your administrator account') }}</div>

        <div class="mb-3">
            <label class="form-label required" for="name">{{ __('Full name') }}</label>
            <div class="input-group input-group-flat">
                <span class="input-group-text"><i class="ti ti-user"></i></span>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" @class(['form-control', 'is-invalid' => $errors->has('name')])>
            </div>
            <x-field-error name="name" />
        </div>

        <div class="mb-3">
            <label class="form-label required" for="email">{{ __('Work email') }}</label>
            <div class="input-group input-group-flat">
                <span class="input-group-text"><i class="ti ti-mail"></i></span>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" @class(['form-control', 'is-invalid' => $errors->has('email')]) placeholder="you@company.com">
            </div>
            <x-field-error name="email" />
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

        <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">{{ __('Create my help desk') }} <i class="ti ti-arrow-right icon ms-1"></i></button>
    </form>

    <x-slot:footer>
        <div class="text-center text-secondary mt-4">
            {{ __('Already have an account?') }} <a href="{{ route('login') }}" class="fw-medium">{{ __('Log in') }}</a>
        </div>
        <div class="text-center text-secondary small mt-2">
            {{ __('Joining your company? Ask your administrator for your team\'s join link.') }}
        </div>
    </x-slot:footer>
</x-layouts.guest>
