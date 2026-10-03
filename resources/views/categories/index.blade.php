<x-layouts.app :title="__('Categories')">
    <x-slot:header>
        <x-page-header :pretitle="__('Configuration')" :title="__('Categories')">
            <x-slot:actions>
                <a href="{{ route('categories.create') }}" class="btn btn-primary"><i class="ti ti-plus icon"></i> {{ __('Add category') }}</a>
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
                        <th>{{ __('Team') }}</th>
                        <th>{{ __('Tickets') }}</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="fw-medium"><i class="ti {{ $category->icon ?? 'ti-help-circle' }} text-primary me-2"></i>{{ __($category->name) }}</td>
                            <td class="text-secondary">{{ __((string) $category->description) }}</td>
                            <td>@if ($category->defaultTeam)<span class="badge bg-{{ $category->defaultTeam->color }}-lt">{{ $category->defaultTeam->name }}</span>@else<span class="text-secondary">—</span>@endif</td>
                            <td>{{ $category->tickets_count }}</td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('categories.destroy', $category) }}" data-confirm="{{ __('Delete this category? Its tickets will become uncategorized.') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-ghost-danger">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">{{ __('No categories yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
