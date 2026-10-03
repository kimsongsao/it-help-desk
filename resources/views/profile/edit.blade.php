<x-layouts.app :title="__('My profile')">
    <x-slot:header>
        <x-page-header :pretitle="__('Account')" :title="__('My profile')">
            <div class="text-secondary mt-1">{{ __(':role at :company', ['role' => __($user->role->name), 'company' => $user->organization->name]) }}</div>
        </x-page-header>
    </x-slot:header>

    <div class="row row-cards">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="card" novalidate>
                @csrf
                @method('PUT')
                <div class="card-header"><h3 class="card-title">{{ __('Personal details') }}</h3></div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <x-avatar :user="$user" size="xl" data-avatar-preview />
                        <div>
                            <label class="btn btn-outline-primary btn-sm mb-1" for="avatar">
                                <i class="ti ti-camera icon"></i> {{ $user->avatar_path ? __('Change photo') : __('Upload photo') }}
                            </label>
                            <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="d-none" data-avatar-input>
                            <div class="text-secondary small">{{ __('JPG, PNG or WebP, up to 2 MB. You can crop it after choosing.') }}</div>
                            <div class="small text-primary d-none" data-avatar-name></div>
                        </div>
                    </div>
                    <x-field-error name="avatar" class="mt-n3 mb-3" />

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required" for="name">{{ __('Full name') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" @class(['form-control', 'is-invalid' => $errors->has('name')])>
                            <x-field-error name="name" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label @class(['form-label', 'required' => $user->email !== null]) for="email">{{ __('Email address') }}</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" @class(['form-control', 'is-invalid' => $errors->has('email')])>
                            @if ($user->email === null)
                                <div class="form-hint">{{ __('Add an email address to receive notifications by email.') }}</div>
                            @endif
                            <x-field-error name="email" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="department_id">{{ __('Department') }}</label>
                            <select id="department_id" name="department_id" @class(['form-select', 'is-invalid' => $errors->has('department_id')])>
                                <option value="">{{ __('No department') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected((int) old('department_id', $user->department_id) === $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                            <x-field-error name="department_id" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="locale">{{ __('Language') }}</label>
                            <select id="locale" name="locale" class="form-select">
                                @foreach (config('helpdesk.locales') as $locale => $language)
                                    <option value="{{ $locale }}" @selected(old('locale', $user->locale ?? app()->getLocale()) === $locale)>{{ $language['name'] }}</option>
                                @endforeach
                            </select>
                            <div class="form-hint">{{ __('Used for the interface, emails and notifications.') }}</div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label" for="timezone">{{ __('Time zone') }}</label>
                            <x-timezone-select :selected="old('timezone', $user->timezone)" :default="$user->organization->timezone" />
                            <div class="form-hint">{{ __('All dates are shown in this time zone. It is now :time there.', ['time' => now()->forViewer()->translatedFormat('H:i')]) }}</div>
                            <x-field-error name="timezone" />
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center">
                    @if ($user->avatar_path)
                        <button type="submit" form="remove-avatar" class="btn btn-ghost-danger"><i class="ti ti-trash icon"></i> {{ __('Remove photo') }}</button>
                    @endif
                    <button type="submit" class="btn btn-primary ms-auto">{{ __('Save changes') }}</button>
                </div>
            </form>
            <div class="modal modal-blur fade" id="avatar-cropper" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Crop your photo') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="avatar-crop-stage mx-auto" data-crop-stage tabindex="0" aria-label="{{ __('Drag to position your photo. Use the arrow keys to move it.') }}">
                                <img alt="" data-crop-image draggable="false">
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-3">
                                <i class="ti ti-zoom-out text-secondary"></i>
                                <input type="range" class="form-range" min="1" max="4" step="0.01" value="1" data-crop-zoom aria-label="{{ __('Zoom') }}">
                                <i class="ti ti-zoom-in text-secondary"></i>
                            </div>
                            <div class="text-secondary small text-center mt-1">{{ __('Drag to move · scroll or slide to zoom') }}</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="button" class="btn btn-primary ms-auto" data-crop-apply><i class="ti ti-crop icon"></i> {{ __('Use photo') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            @if ($user->avatar_path)
                <form id="remove-avatar" method="POST" action="{{ route('profile.avatar.destroy') }}" data-confirm="{{ __('Remove your profile photo?') }}">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </div>

        <div class="col-lg-5">
            <form method="POST" action="{{ route('profile.password.update') }}" class="card" novalidate>
                @csrf
                @method('PUT')
                <div class="card-header">
                    <h3 class="card-title">{{ $user->hasPassword() ? __('Change password') : __('Set a password') }}</h3>
                </div>
                <div class="card-body">
                    @unless ($user->hasPassword())
                        <div class="alert alert-info">{{ __('You signed up with a social account. Set a password to also log in with your email.') }}</div>
                    @endunless
                    @if ($user->hasPassword())
                        <div class="mb-3">
                            <label class="form-label required" for="current_password">{{ __('Current password') }}</label>
                            <x-password-input name="current_password" />
                            @error('current_password', 'password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label required" for="password">{{ __('New password') }}</label>
                        <x-password-input name="password" autocomplete="new-password" />
                        @error('password', 'password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label required" for="password_confirmation">{{ __('Confirm new password') }}</label>
                        <x-password-input name="password_confirmation" autocomplete="new-password" />
                    </div>
                    @if ($user->hasPassword())
                        <div class="form-hint mt-2">{{ __('You will be signed out on your other devices.') }}</div>
                    @endif
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">{{ $user->hasPassword() ? __('Change password') : __('Set password') }}</button>
                </div>
            </form>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">{{ __('Connected accounts') }}</h3></div>
                @error('social')
                    <div class="alert alert-danger m-3 mb-0">{{ $message }}</div>
                @enderror
                <div class="list-group list-group-flush">
                    @foreach ($providers as $provider)
                        @php($account = $socialAccounts->get($provider->value))
                        <div class="list-group-item d-flex align-items-center gap-3">
                            <i class="ti ti-brand-{{ $provider->value }} fs-1 text-secondary"></i>
                            <div class="flex-fill">
                                <div class="fw-medium">{{ $provider->label() }}</div>
                                <div class="text-secondary small">
                                    {{ $account ? __('Connected · last used :time', ['time' => $account->last_login_at?->diffForHumans() ?? '—']) : __('Not connected') }}
                                </div>
                            </div>
                            @if ($account)
                                <form method="POST" action="{{ route('profile.social-accounts.destroy', $account) }}" data-confirm="{{ __('Disconnect :provider?', ['provider' => $provider->label()]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-ghost-danger">{{ __('Disconnect') }}</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="card-footer text-secondary small">
                    {{ __('To connect an account, log out and choose "Continue with Google / Facebook / Telegram" using the same verified email.') }}
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
