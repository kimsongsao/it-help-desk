<x-layouts.app :title="$user->exists ? __('Edit user') : __('New user')">
    <x-slot:header>
        <x-page-header :pretitle="__('Users')" :title="$user->exists ? $user->name : __('New user')" />
    </x-slot:header>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="card">
                @csrf
                @if ($user->exists)
                    @method('PATCH')
                @endif
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required" for="name">{{ __('Name') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required @class(['form-control', 'is-invalid' => $errors->has('name')])>
                            <x-field-error name="name" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required" for="email">{{ __('Email') }}</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required @class(['form-control', 'is-invalid' => $errors->has('email')])>
                            <x-field-error name="email" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="department">{{ __('Department') }}</label>
                            <input id="department" name="department" type="text" value="{{ old('department', $user->department) }}" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required" for="role_id">{{ __('Role') }}</label>
                            <select id="role_id" name="role_id" required @class(['form-select', 'is-invalid' => $errors->has('role_id')])>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected((int) old('role_id', $user->role_id ?? App\Models\Role::defaultFor(auth()->user()->organization_id)->id) === $role->id)>{{ __($role->name) }}</option>
                                @endforeach
                            </select>
                            <x-field-error name="role_id" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label @class(['form-label', 'required' => ! $user->exists]) for="password">{{ __('Password') }}</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" @class(['form-control', 'is-invalid' => $errors->has('password')])>
                            @if ($user->exists)<div class="form-hint">{{ __('Leave blank to keep the current password.') }}</div>@endif
                            <x-field-error name="password" />
                        </div>
                    </div>
                    <label class="form-check form-switch mb-0">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $user->is_active))>
                        <span class="form-check-label">{{ __('Account is active') }}</span>
                    </label>
                    <x-field-error name="is_active" />
                    <div class="form-hint">{{ __('Deactivated users cannot sign in and their API tokens are revoked.') }}</div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('users.index') }}" class="btn btn-link">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Save user') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
