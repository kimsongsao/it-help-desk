@php($user = auth()->user())

<x-layouts.app :title="__('Home')">
    <x-slot:header>
        <x-page-header :pretitle="now()->forViewer()->translatedFormat('l, j F Y')" :title="__('Hello, :name', ['name' => str($user->name)->before(' ')])">
            <div class="text-secondary mt-1">
                {{ $user->isStaff() ? __("Here's what needs your attention today.") : __('How can we help you today?') }}
            </div>
        </x-page-header>
    </x-slot:header>

    @unless ($user->isStaff())
        @can('tickets.create')
            <div class="card hero-card mb-3">
                <div class="card-body p-4">
                    <div class="row align-items-center g-3">
                        <div class="col-lg">
                            <h2 class="mb-1">{{ __('Need help with something?') }}</h2>
                            <p class="text-secondary mb-0">{{ __('Tell us what happened. You can type, speak or record a voice note, and attach screenshots.') }}</p>
                        </div>
                        <div class="col-lg-auto">
                            <a href="{{ route('tickets.create') }}" class="btn btn-primary btn-lg w-100"><i class="ti ti-plus icon"></i> {{ __('Submit a ticket') }}</a>
                        </div>
                    </div>
                    @if ($categories->isNotEmpty())
                        <div class="hr-text text-secondary">{{ __('Or start with a topic') }}</div>
                        <div class="row g-2">
                            @foreach ($categories as $category)
                                <div class="col-6 col-sm-4 col-lg">
                                    <a href="{{ route('tickets.create', ['category' => $category->id]) }}" class="card card-sm card-link card-link-pop text-center h-100">
                                        <div class="card-body px-2">
                                            <i class="ti {{ $category->icon ?? 'ti-help-circle' }} fs-1 text-primary d-block mb-1"></i>
                                            <div class="small fw-medium">{{ __($category->name) }}</div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endcan
    @endunless

    <div class="row row-deck row-cards">
        @foreach ($statuses as $status)
            <div class="col-6 col-sm-4 col-lg">
                <a href="{{ route('tickets.index', ['status' => $status->value]) }}" class="card card-sm card-link">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="avatar bg-{{ $status->color() }}-lt"><i class="ti ti-ticket"></i></span></div>
                            <div class="col">
                                <div class="h3 mb-0">{{ $statusCounts[$status->value] ?? 0 }}</div>
                                <div class="text-secondary">{{ $status->label() }}</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    @if ($slaCounts)
        <div class="row row-deck row-cards mt-0">
            @foreach ([
                ['unassigned', __('Unassigned'), 'ti-user-question', 'azure', ['assignee' => 'unassigned']],
                ['at_risk', __('SLA at risk'), 'ti-alarm', 'orange', ['sla' => 'at_risk']],
                ['breached', __('SLA breached'), 'ti-alarm-off', 'red', ['sla' => 'breached']],
                ['escalated', __('Escalated'), 'ti-arrow-big-up-lines', 'purple', ['sla' => 'escalated']],
            ] as [$key, $label, $icon, $color, $query])
                <div class="col-6 col-lg-3">
                    <a href="{{ route('tickets.index', $query) }}" class="card card-sm card-link">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto"><span class="avatar bg-{{ $color }} text-white"><i class="ti {{ $icon }}"></i></span></div>
                                <div class="col">
                                    <div class="h3 mb-0">{{ $slaCounts[$key] }}</div>
                                    <div class="text-secondary">{{ $label }}</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        @if ($isInTeam && $user->organization->hasFeature(App\Enums\Feature::Teams))
            <div class="card mt-3">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('Team queue') }}</h3>
                        <div class="card-subtitle">{{ __('Unassigned tickets waiting in your teams') }}</div>
                    </div>
                    <div class="card-actions"><a href="{{ route('tickets.index', ['team' => 'mine', 'assignee' => 'unassigned']) }}" class="btn btn-sm">{{ __('View all') }}</a></div>
                </div>
                <x-ticket-table :tickets="$teamQueue">
                    <x-slot:empty>
                        <x-empty-state icon="ti-circle-check" :title="__('Nothing waiting')" :subtitle="__('Every ticket in your teams has an owner.')" />
                    </x-slot:empty>
                </x-ticket-table>
            </div>
        @endif

        <div class="card mt-3">
            <div class="card-header">
                <div>
                    <h3 class="card-title">{{ __('My queue') }}</h3>
                    <div class="card-subtitle">{{ __('Your open tickets, most urgent deadline first') }}</div>
                </div>
                <div class="card-actions"><a href="{{ route('tickets.index', ['assignee' => 'me']) }}" class="btn btn-sm">{{ __('View all') }}</a></div>
            </div>
            <x-ticket-table :tickets="$myQueue">
                <x-slot:empty>
                    <x-empty-state icon="ti-mug" :title="__('Your queue is empty')" :subtitle="__('Pick up an unassigned ticket to get started.')">
                        <x-slot:action>
                            <a href="{{ route('tickets.index', ['assignee' => 'unassigned']) }}" class="btn btn-primary">{{ __('Show unassigned tickets') }}</a>
                        </x-slot:action>
                    </x-empty-state>
                </x-slot:empty>
            </x-ticket-table>
        </div>
    @endif

    @unless ($user->isStaff())
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Your open requests') }}</h3>
                <div class="card-actions"><a href="{{ route('tickets.index') }}" class="btn btn-sm">{{ __('View all') }}</a></div>
            </div>
            <div class="list-group list-group-flush list-group-hoverable">
                @forelse ($openRequests as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}" class="list-group-item list-group-item-action">
                        <div class="row align-items-center g-3">
                            <div class="col-auto"><span class="avatar bg-{{ $ticket->status->color() }}-lt"><i class="ti {{ $ticket->category?->icon ?? 'ti-ticket' }}"></i></span></div>
                            <div class="col text-truncate">
                                <div class="fw-medium text-truncate">{{ $ticket->subject }}</div>
                                <div class="text-secondary small">
                                    {{ $ticket->reference }} · {{ __('Updated :time', ['time' => $ticket->updated_at->diffForHumans()]) }}
                                    · {{ $ticket->assignee ? __('Handled by :name', ['name' => $ticket->assignee->name]) : __('Waiting for an agent') }}
                                </div>
                            </div>
                            <div class="col-auto"><x-badge :value="$ticket->status" /></div>
                        </div>
                    </a>
                @empty
                    <x-empty-state icon="ti-circle-check" :title="__('No open requests')" :subtitle="__('Everything is working? Great! Submit a ticket whenever you need help.')" />
                @endforelse
            </div>
        </div>
    @else
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Recent activity') }}</h3>
                <div class="card-actions"><a href="{{ route('tickets.index') }}" class="btn btn-sm">{{ __('View all') }}</a></div>
            </div>
            <x-ticket-table :tickets="$recentTickets" :show-requester="$user->can('tickets.view_all')" />
        </div>
    @endunless
</x-layouts.app>
