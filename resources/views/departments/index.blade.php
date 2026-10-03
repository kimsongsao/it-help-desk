<x-layouts.app :title="__('Departments')">
    <x-slot:header>
        <x-page-header :pretitle="__('Administration')" :title="__('Departments')">
            <div class="text-secondary mt-1">{{ __('Where people in your organization work. Used in reports, exports and workflow conditions.') }}</div>
            <x-slot:actions>
                <a href="{{ route('departments.create') }}" class="btn btn-primary"><i class="ti ti-plus icon"></i> {{ __('Add department') }}</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th class="text-end">{{ __('People') }}</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr>
                            <td class="fw-medium">{{ $department->name }}</td>
                            <td class="text-secondary">{{ $department->description }}</td>
                            <td class="text-end"><a href="{{ route('users.index', ['department' => $department->id]) }}">{{ $department->users_count }}</a></td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="{{ route('departments.edit', $department) }}" class="btn btn-sm">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('departments.destroy', $department) }}" data-confirm="{{ __('Delete this department? Its people will have no department.') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-ghost-danger">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty-state icon="ti-building" :title="__('No departments yet')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
