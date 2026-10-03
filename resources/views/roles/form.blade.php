<x-layouts.app :title="$role->exists ? __('Edit role') : __('New role')">
    <x-slot:header>
        <x-page-header :pretitle="__('Roles')" :title="$role->exists ? $role->name : __('New role')" />
    </x-slot:header>

    <form method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}">
        @csrf
        @if ($role->exists)
            @method('PATCH')
        @endif

        <div class="row row-cards">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required" for="name">{{ __('Name') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $role->name) }}" required maxlength="100" @class(['form-control', 'is-invalid' => $errors->has('name')])>
                            <x-field-error name="name" />
                        </div>
                        <div>
                            <label class="form-label" for="description">{{ __('Description') }}</label>
                            <textarea id="description" name="description" rows="3" maxlength="255" class="form-control">{{ old('description', $role->description) }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('roles.index') }}" class="btn btn-link">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Save role') }}</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">{{ __('Permissions') }}</h3></div>
                    <div class="card-body">
                        @if ($role->isAdministrator())
                            <div class="alert alert-info mb-0">{{ __('The administrator role always has every permission.') }}</div>
                        @else
                            @php($granted = old('permissions', $role->permissions->map->value->all()))
                            <div class="row">
                                @foreach (App\Enums\Permission::grouped() as $group => $permissions)
                                    <div class="col-md-6 mb-3">
                                        <div class="subheader mb-2">{{ __($group) }}</div>
                                        @foreach ($permissions as $permission)
                                            <label class="form-check">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->value }}" class="form-check-input" @checked(in_array($permission->value, $granted, true))>
                                                <span class="form-check-label">{{ $permission->label() }}</span>
                                                <span class="form-check-description"><code>{{ $permission->value }}</code></span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.app>
