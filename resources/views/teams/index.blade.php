<x-layouts.app :title="__('Teams')">
    <x-slot:header>
        <x-page-header :pretitle="__('Configuration')" :title="__('Teams')">
            <div class="text-secondary mt-1">{{ __('Group your agents so tickets reach the right people. Categories can route new tickets straight to a team.') }}</div>
            <x-slot:actions>
                <a href="{{ route('teams.create') }}" class="btn btn-primary"><i class="ti ti-plus icon"></i> {{ __('Add team') }}</a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="row row-cards">
        @forelse ($teams as $team)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-status-top bg-{{ $team->color }}"></div>
                    <div class="card-header">
                        <div>
                            <h3 class="card-title"><span class="badge bg-{{ $team->color }} me-2"></span>{{ $team->name }}</h3>
                            @if ($team->description)<div class="card-subtitle">{{ $team->description }}</div>@endif
                        </div>
                        <div class="card-actions"><a href="{{ route('teams.edit', $team) }}" class="btn btn-sm">{{ __('Edit') }}</a></div>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-3">
                            <div class="col">
                                <a href="{{ route('tickets.index', ['team' => $team->id]) }}" class="text-reset text-decoration-none">
                                    <div class="h2 mb-0">{{ $team->open_tickets_count }}</div>
                                    <div class="text-secondary small">{{ __('Open') }}</div>
                                </a>
                            </div>
                            <div class="col">
                                <a href="{{ route('tickets.index', ['team' => $team->id, 'assignee' => 'unassigned']) }}" class="text-reset text-decoration-none">
                                    <div @class(['h2 mb-0', 'text-red' => $team->unassigned_tickets_count > 0])>{{ $team->unassigned_tickets_count }}</div>
                                    <div class="text-secondary small">{{ __('Unassigned') }}</div>
                                </a>
                            </div>
                            <div class="col">
                                <div class="h2 mb-0">{{ $team->members->count() }}</div>
                                <div class="text-secondary small">{{ __('Members') }}</div>
                            </div>
                        </div>
                        <div class="avatar-list avatar-list-stacked mb-3">
                            @foreach ($team->members->take(8) as $member)
                                <x-avatar :user="$member" size="sm" />
                            @endforeach
                            @if ($team->members->count() > 8)
                                <span class="avatar avatar-sm">+{{ $team->members->count() - 8 }}</span>
                            @endif
                        </div>
                        @if ($team->categories->isNotEmpty())
                            <div class="text-secondary small mb-1">{{ __('Receives new tickets from') }}</div>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach ($team->categories as $category)
                                    <span class="badge bg-secondary-lt"><i class="ti {{ $category->icon ?? 'ti-category' }}"></i> {{ __($category->name) }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <x-empty-state icon="ti-users-group" :title="__('No teams yet')" :subtitle="__('Create teams like Service Desk, Network or Billing and route tickets to them.')">
                        <x-slot:action>
                            <a href="{{ route('teams.create') }}" class="btn btn-primary"><i class="ti ti-plus icon"></i> {{ __('Add team') }}</a>
                        </x-slot:action>
                    </x-empty-state>
                </div>
            </div>
        @endforelse
    </div>
</x-layouts.app>
