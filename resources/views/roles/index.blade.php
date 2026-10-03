<x-layouts.app :title="__('Roles')">
    <x-slot:header>
        <x-page-header :pretitle="__('Administration')" :title="__('Roles & permissions')">
            <x-slot:actions>
                <a href="{{ route('roles.create') }}" class="btn btn-primary"><i class="ti ti-plus icon"></i> {{ __('Add role') }}</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="row row-cards">
        @foreach ($roles as $role)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">{{ __($role->name) }}</h3>
                            <div class="text-secondary small">{{ $role->users_count }} {{ str('user')->plural($role->users_count) }}</div>
                        </div>
                        <div class="card-actions btn-list">
                            @if ($role->is_system)<span class="badge bg-secondary-lt">{{ __('Built-in') }}</span>@endif
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm">{{ __('Edit') }}</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary">{{ __((string) $role->description) }}</p>
                        @if ($role->isAdministrator())
                            <span class="badge bg-green-lt">{{ __('All permissions') }}</span>
                        @else
                            <div class="d-flex flex-wrap gap-1">
                                @forelse ($role->permissions as $permission)
                                    <span class="badge bg-blue-lt">{{ $permission->label() }}</span>
                                @empty
                                    <span class="text-secondary">{{ __('No permissions') }}</span>
                                @endforelse
                            </div>
                        @endif
                    </div>
                    @unless ($role->is_system)
                        <div class="card-footer">
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" data-confirm="{{ __('Delete this role?') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost-danger" @disabled($role->users_count > 0)>{{ __('Delete') }}</button>
                            </form>
                        </div>
                    @endunless
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.app>
