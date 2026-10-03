<x-layouts.app :title="__('Organizations')">
    <x-slot:header>
        <x-page-header :pretitle="__('Platform')" :title="__('Organizations')">
            <x-slot:actions>
                <a href="{{ route('platform.organizations.create') }}" class="btn btn-primary"><i class="ti ti-plus icon"></i> {{ __('Add organization') }}</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="row row-cards mb-3">
        @foreach ([
            [__('Organizations'), $totals['organizations'], 'ti-building', 'blue'],
            [__('Users'), $totals['users'], 'ti-users', 'green'],
            [__('Tickets'), $totals['tickets'], 'ti-ticket', 'orange'],
        ] as [$label, $value, $icon, $color])
            <div class="col-sm-4">
                <div class="card card-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar bg-{{ $color }} text-white"><i class="ti {{ $icon }}"></i></span>
                        <div><div class="h3 mb-0">{{ number_format($value) }}</div><div class="text-secondary">{{ $label }}</div></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body border-bottom py-3">
            <form method="GET" class="row g-2">
                <div class="col">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('Search organizations…') }}">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="plan" class="form-select" aria-label="{{ __('Plan') }}">
                        <option value="">{{ __('All plans') }}</option>
                        @foreach (App\Enums\Plan::cases() as $plan)
                            <option value="{{ $plan->value }}" @selected(($filters['plan'] ?? null) === $plan->value)>{{ $plan->label() }} ({{ $totals['byPlan'][$plan->value] ?? 0 }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-primary">{{ __('Filter') }}</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                    <tr>
                        <th>{{ __('Organization') }}</th>
                        <th>{{ __('Plan') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Users') }}</th>
                        <th class="text-end">{{ __('Tickets') }}</th>
                        <th>{{ __('Created') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($organizations as $organization)
                        <tr data-href="{{ route('platform.organizations.edit', $organization) }}">
                            <td>
                                <a href="{{ route('platform.organizations.edit', $organization) }}" class="text-reset fw-medium">{{ $organization->name }}</a>
                                <div class="text-secondary small">{{ $organization->slug }}</div>
                            </td>
                            <td><span class="badge bg-{{ $organization->plan->color() }}-lt">{{ $organization->plan->label() }}</span></td>
                            <td>
                                @if ($organization->is_active)
                                    <span class="status status-green"><span class="status-dot"></span> {{ __('Active') }}</span>
                                @else
                                    <span class="status status-red"><span class="status-dot"></span> {{ __('Suspended') }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $organization->users_count }}</td>
                            <td class="text-end">{{ $organization->tickets_count }}</td>
                            <td class="text-secondary">{{ $organization->created_at->translatedFormat('j M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="ti-building" :title="__('No organizations found')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($organizations->hasPages())
            <div class="card-footer">{{ $organizations->links() }}</div>
        @endif
    </div>
</x-layouts.app>
