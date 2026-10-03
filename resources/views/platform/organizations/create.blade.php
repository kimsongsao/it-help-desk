<x-layouts.app :title="__('Add organization')">
    <x-slot:header>
        <x-page-header :pretitle="__('Platform')" :title="__('Add organization')" />
    </x-slot:header>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('platform.organizations.store') }}" class="card">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label required" for="company_name">{{ __('Company name') }}</label>
                            <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required @class(['form-control', 'is-invalid' => $errors->has('company_name')])>
                            <x-field-error name="company_name" />
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label required" for="plan">{{ __('Plan') }}</label>
                            <select id="plan" name="plan" class="form-select">
                                @foreach (App\Enums\Plan::cases() as $plan)
                                    <option value="{{ $plan->value }}" @selected(old('plan', 'enterprise') === $plan->value)>{{ $plan->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label required" for="template">{{ __('Template') }}</label>
                            <select id="template" name="template" class="form-select">
                                @foreach (App\Enums\HelpDeskTemplate::cases() as $template)
                                    <option value="{{ $template->value }}" @selected(old('template') === $template->value)>{{ $template->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="hr-text text-secondary">{{ __('Administrator') }}</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required" for="name">{{ __('Full name') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required @class(['form-control', 'is-invalid' => $errors->has('name')])>
                            <x-field-error name="name" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required" for="email">{{ __('Email') }}</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required @class(['form-control', 'is-invalid' => $errors->has('email')])>
                            <x-field-error name="email" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="password">{{ __('Temporary password') }}</label>
                            <x-password-input autocomplete="new-password" />
                            <x-field-error name="password" />
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('platform.organizations.index') }}" class="btn btn-link">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Create organization') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
