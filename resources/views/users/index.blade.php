<x-layouts.app :title="__('Users')">
    <x-slot:header>
        <x-page-header :pretitle="__('Administration')" :title="__('Users')">
            <x-slot:actions>
                <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="ti ti-user-plus icon"></i> {{ __('Add user') }}</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="card">
        <div class="card-body border-bottom py-3">
            <form method="GET" action="{{ route('users.index') }}" class="row g-2">
                <div class="col-md-5">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('Search by name or email…') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">{{ __('All roles') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((int) ($filters['role'] ?? 0) === $role->id)>{{ __($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-primary">{{ __('Filter') }}</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Department') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Submitted') }}</th>
                        <th class="text-end">{{ __('Assigned') }}</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2">{{ mb_substr($user->name, 0, 1) }}</span>
                                    <div>
                                        <div class="fw-medium">{{ $user->name }} @if ($user->is(auth()->user()))<span class="text-secondary">{{ __('(you)') }}</span>@endif</div>
                                        <div class="text-secondary small">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-secondary">{{ $user->department ?? '—' }}</td>
                            <td><span class="badge bg-blue-lt">{{ __($user->role->name) }}</span></td>
                            <td>
                                @if ($user->is_active)
                                    <span class="status status-green"><span class="status-dot"></span> {{ __('Active') }}</span>
                                @else
                                    <span class="status status-secondary"><span class="status-dot"></span> {{ __('Deactivated') }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $user->requested_tickets_count }}</td>
                            <td class="text-end">{{ $user->assigned_tickets_count }}</td>
                            <td><a href="{{ route('users.edit', $user) }}" class="btn btn-sm">{{ __('Edit') }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="card-footer d-flex align-items-center">{{ $users->links() }}</div>
        @endif
    </div>
</x-layouts.app>
