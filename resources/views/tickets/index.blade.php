@php
    $user = auth()->user();
    $activeStatus = $filters['status'] ?? null;
    $hasExtraFilters = filled($filters['priority'] ?? null) || filled($filters['category'] ?? null) || filled($filters['assignee'] ?? null) || filled($filters['sla'] ?? null) || filled($filters['team'] ?? null);
@endphp

<x-layouts.app :title="__('Tickets')">
    <x-slot:header>
        <x-page-header :pretitle="__('Help desk')" :title="$user->can('tickets.view_all') ? __('Tickets') : __('My tickets')">
            <x-slot:actions>
                @can('tickets.create')
                    <a href="{{ route('tickets.create') }}" class="btn btn-primary d-none d-md-inline-flex"><i class="ti ti-plus icon"></i> {{ __('New ticket') }}</a>
                @endcan
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a href="{{ route('tickets.index', Arr::except($filters, 'status')) }}" @class(['nav-link text-nowrap', 'active' => ! $activeStatus])>
                        {{ __('All') }} <span class="badge bg-secondary-lt ms-2">{{ $statusCounts->sum() }}</span>
                    </a>
                </li>
                @foreach (App\Enums\TicketStatus::cases() as $status)
                    <li class="nav-item">
                        <a href="{{ route('tickets.index', [...Arr::except($filters, 'status'), 'status' => $status->value]) }}" @class(['nav-link text-nowrap', 'active' => $activeStatus === $status->value])>
                            {{ $status->label() }} <span class="badge bg-{{ $status->color() }}-lt ms-2">{{ $statusCounts[$status->value] ?? 0 }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body border-bottom py-3">
            <form method="GET" action="{{ route('tickets.index') }}">
                @if ($activeStatus)
                    <input type="hidden" name="status" value="{{ $activeStatus }}">
                @endif
                <div class="row g-2">
                    <div class="col">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ __('Search by subject or description…') }}">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn" data-bs-toggle="collapse" data-bs-target="#more-filters" aria-expanded="{{ $hasExtraFilters ? 'true' : 'false' }}">
                            <i class="ti ti-adjustments-horizontal icon"></i> <span class="d-none d-sm-inline">{{ __('Filters') }}</span>
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-search icon"></i> <span class="d-none d-sm-inline">{{ __('Search') }}</span></button>
                    </div>
                </div>
                <div @class(['collapse', 'show' => $hasExtraFilters]) id="more-filters">
                    <div class="row g-2 mt-1">
                        <div class="col-6 col-lg">
                            <select name="priority" class="form-select" aria-label="{{ __('Priority') }}">
                                <option value="">{{ __('Any priority') }}</option>
                                @foreach (App\Enums\TicketPriority::cases() as $priority)
                                    <option value="{{ $priority->value }}" @selected(($filters['priority'] ?? null) === $priority->value)>{{ $priority->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-lg">
                            <select name="category" class="form-select" aria-label="{{ __('Category') }}">
                                <option value="">{{ __('Any category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) ($filters['category'] ?? 0) === $category->id)>{{ __($category->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($user->isStaff())
                            <div class="col-6 col-lg">
                                <select name="assignee" class="form-select" aria-label="{{ __('Assignee') }}">
                                    <option value="">{{ __('Anyone') }}</option>
                                    <option value="me" @selected(($filters['assignee'] ?? null) === 'me')>{{ __('Assigned to me') }}</option>
                                    <option value="unassigned" @selected(($filters['assignee'] ?? null) === 'unassigned')>{{ __('Unassigned') }}</option>
                                </select>
                            </div>
                            @if ($teams->isNotEmpty())
                                <div class="col-6 col-lg">
                                    <select name="team" class="form-select" aria-label="{{ __('Team') }}">
                                        <option value="">{{ __('Any team') }}</option>
                                        <option value="mine" @selected(($filters['team'] ?? null) === 'mine')>{{ __('My teams') }}</option>
                                        <option value="none" @selected(($filters['team'] ?? null) === 'none')>{{ __('No team') }}</option>
                                        @foreach ($teams as $team)
                                            <option value="{{ $team->id }}" @selected((string) ($filters['team'] ?? '') === (string) $team->id)>{{ $team->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-6 col-lg">
                                <select name="sla" class="form-select" aria-label="{{ __('SLA') }}">
                                    <option value="">{{ __('Any SLA state') }}</option>
                                    <option value="at_risk" @selected(($filters['sla'] ?? null) === 'at_risk')>{{ __('At risk') }}</option>
                                    <option value="breached" @selected(($filters['sla'] ?? null) === 'breached')>{{ __('Breached') }}</option>
                                    <option value="escalated" @selected(($filters['sla'] ?? null) === 'escalated')>{{ __('Escalated') }}</option>
                                </select>
                            </div>
                        @endif
                        <div class="col-12 col-lg-auto">
                            <a href="{{ route('tickets.index') }}" class="btn btn-ghost-secondary w-100"><i class="ti ti-x icon"></i> {{ __('Clear filters') }}</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <x-ticket-table :tickets="$tickets" :show-requester="$user->can('tickets.view_all')" :show-sla="$user->isStaff()">
            @if (! $user->isStaff() && $statusCounts->sum() === 0)
                <x-slot:empty>
                    <x-empty-state icon="ti-mood-smile" :title="__('You have not submitted any tickets yet')" :subtitle="__('When something breaks, we are here to help.')">
                        <x-slot:action>
                            <a href="{{ route('tickets.create') }}" class="btn btn-primary"><i class="ti ti-plus icon"></i> {{ __('Submit a ticket') }}</a>
                        </x-slot:action>
                    </x-empty-state>
                </x-slot:empty>
            @endif
        </x-ticket-table>

        @if ($tickets->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
