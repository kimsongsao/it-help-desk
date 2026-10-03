@php
    use App\Enums\TicketStatus;

    $user = auth()->user();
    $canUpdate = $user->can('update', $ticket);
    $canAssign = $user->can('assign', $ticket);
    $quickActions = collect([
        $canAssign && $ticket->assignee_id !== $user->id && $user->isStaff() ? ['assignee_id', $user->id, __('Assign to me'), 'ti-user-check', 'btn-outline-primary'] : null,
        $canUpdate && in_array($ticket->status, [TicketStatus::Open, TicketStatus::OnHold], true) ? ['status', TicketStatus::InProgress->value, __('Start working'), 'ti-player-play', 'btn-outline-primary'] : null,
        $canUpdate && $ticket->status === TicketStatus::InProgress ? ['status', TicketStatus::OnHold->value, __('Put on hold'), 'ti-player-pause', 'btn-outline-secondary'] : null,
        $canUpdate && $ticket->status->isActive() ? ['status', TicketStatus::Resolved->value, __('Mark resolved'), 'ti-circle-check', 'btn-success'] : null,
        $canUpdate && ! $ticket->status->isActive() ? ['status', TicketStatus::Open->value, __('Reopen'), 'ti-refresh', 'btn-outline-secondary'] : null,
    ])->filter();
@endphp

<x-layouts.app :title="$ticket->reference">
    <x-slot:header>
        <x-page-header :pretitle="$ticket->reference" :title="$ticket->subject">
            <div class="d-flex flex-wrap gap-2 mt-2">
                <x-badge :value="$ticket->status" />
                <x-badge :value="$ticket->priority" />
                <span class="badge bg-secondary-lt"><i class="ti {{ $ticket->category?->icon ?? 'ti-category' }}"></i> {{ $ticket->category ? __($ticket->category->name) : __('Uncategorized') }}</span>
                @if ($ticket->escalation_level > 0)
                    <span class="badge bg-red-lt"><i class="ti ti-arrow-big-up-lines"></i> {{ __('Escalation level :level', ['level' => $ticket->escalation_level]) }}</span>
                @endif
            </div>
            <x-slot:actions>
                @foreach ($quickActions as [$field, $value, $label, $icon, $style])
                    <form method="POST" action="{{ route('tickets.update', $ticket) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                        <button type="submit" class="btn {{ $style }}"><i class="ti {{ $icon }} icon"></i> {{ $label }}</button>
                    </form>
                @endforeach
                @can('escalate', $ticket)
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-escalate">
                        <i class="ti ti-arrow-big-up-lines icon"></i> {{ __('Escalate') }}
                    </button>
                @endcan
                @can('delete', $ticket)
                    <div class="dropdown">
                        <button type="button" class="btn btn-icon btn-ghost-secondary" data-bs-toggle="dropdown" aria-label="{{ __('More actions') }}"><i class="ti ti-dots-vertical"></i></button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" data-confirm="{{ __('Delete this ticket permanently?') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger"><i class="ti ti-trash icon dropdown-item-icon"></i> {{ __('Delete ticket') }}</button>
                            </form>
                        </div>
                    </div>
                @endcan
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    @if ($ticket->requester->is($user))
        <div class="card mb-3">
            <div class="card-body py-3">
                <x-status-steps :ticket="$ticket" />
                <div class="text-secondary small text-center">
                    @if ($ticket->status === TicketStatus::OnHold)
                        {{ __('We are waiting on more information or a third party. Reply below if you can help.') }}
                    @elseif ($ticket->status === TicketStatus::Resolved)
                        {{ __('We think this is fixed. Still having trouble? Just reply and the ticket will reopen.') }}
                    @elseif ($ticket->assignee)
                        {{ __(':name is working on your ticket.', ['name' => $ticket->assignee->name]) }}
                    @elseif ($ticket->status->isActive())
                        {{ __('Your ticket is in the queue. An agent will pick it up soon.') }}
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="row row-cards">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <x-avatar :user="$ticket->requester" class="me-2" />
                        <div>
                            <div class="fw-medium">{{ $ticket->requester->name }}</div>
                            <div class="text-secondary small" title="{{ $ticket->created_at->forViewer()->translatedFormat('j M Y, H:i') }}">{{ __('opened :time', ['time' => $ticket->created_at->diffForHumans()]) }}</div>
                        </div>
                    </div>
                    @if (filled($ticket->description))
                        <div class="ticket-body">{{ $ticket->description }}</div>
                    @endif
                    <x-attachment-list :attachments="$ticket->ticketAttachments" />
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                        <li class="nav-item"><a href="#tab-conversation" class="nav-link active" data-bs-toggle="tab"><i class="ti ti-messages icon me-1"></i> {{ __('Conversation') }} <span class="badge bg-secondary-lt ms-2">{{ $comments->count() }}</span></a></li>
                        @if ($activities->isNotEmpty())
                            <li class="nav-item"><a href="#tab-activity" class="nav-link" data-bs-toggle="tab"><i class="ti ti-history icon me-1"></i> {{ __('Activity') }}</a></li>
                        @endif
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active show" id="tab-conversation">
                        <div class="card-body">
                            @forelse ($comments as $comment)
                                <div @class(['d-flex gap-3 mb-3', 'flex-row-reverse' => $comment->author?->is($user)])>
                                    <x-avatar :user="$comment->author" class="flex-shrink-0" />
                                    <div @class([
                                        'reply-bubble p-3 flex-fill',
                                        'internal-note' => $comment->is_internal,
                                        'bg-primary-lt' => ! $comment->is_internal && $comment->author?->is($user),
                                        'bg-body-tertiary border' => ! $comment->is_internal && ! $comment->author?->is($user),
                                    ]) style="max-width: 85%">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <strong>{{ $comment->author?->name ?? __('Automation') }}</strong>
                                            @if ($comment->author?->isStaff())
                                                <span class="badge bg-primary-lt">{{ __('Support') }}</span>
                                            @endif
                                            @if ($comment->is_internal)
                                                <span class="badge bg-yellow text-yellow-fg"><i class="ti ti-lock"></i> {{ __('Internal note') }}</span>
                                            @endif
                                            <span class="text-secondary small ms-auto" title="{{ $comment->created_at->forViewer()->translatedFormat('j M Y, H:i') }}">{{ $comment->created_at->diffForHumans() }}</span>
                                        </div>
                                        @if (filled($comment->body))
                                            <div class="ticket-body">{{ $comment->body }}</div>
                                        @endif
                                        <x-attachment-list :attachments="$comment->attachments" class="mt-2" />
                                    </div>
                                </div>
                            @empty
                                <x-empty-state icon="ti-message-circle" :title="__('No replies yet')" :subtitle="$user->isStaff() ? __('Be the first to respond.') : __('An agent will reply here. You will also get a notification.')" class="py-3" />
                            @endforelse
                        </div>

                        @can('comment', $ticket)
                            <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" enctype="multipart/form-data" class="card-footer" novalidate>
                                @csrf
                                <label class="form-label" for="body">{{ __('Add a reply') }}</label>
                                <x-composer name="body" :value="old('body')" rows="3" :placeholder="$user->isStaff() ? __('Write a reply to the requester…') : __('Add more details or answer a question…')" />
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                                    @if ($canSeeInternal)
                                        <label class="form-check form-switch mb-0">
                                            <input type="checkbox" name="is_internal" value="1" class="form-check-input">
                                            <span class="form-check-label"><i class="ti ti-lock"></i> {{ __('Internal note (hidden from requester)') }}</span>
                                        </label>
                                    @endif
                                    <button type="submit" class="btn btn-primary ms-auto"><i class="ti ti-send icon"></i> {{ __('Send reply') }}</button>
                                </div>
                            </form>
                        @endcan
                    </div>

                    @if ($activities->isNotEmpty())
                        <div class="tab-pane" id="tab-activity">
                            <div class="card-body">
                                <ul class="timeline">
                                    @foreach ($activities as $activity)
                                        <li class="timeline-event">
                                            <div class="timeline-event-icon bg-{{ $activity->type->color() }}-lt"><i class="ti {{ $activity->type->icon() }}"></i></div>
                                            <div class="card timeline-event-card">
                                                <div class="card-body py-2">
                                                    <div class="text-secondary float-end small" title="{{ $activity->created_at->forViewer()->translatedFormat('j M Y, H:i') }}">{{ $activity->created_at->diffForHumans() }}</div>
                                                    <div>{{ $activity->message() }}</div>
                                                    <div class="text-secondary small">{{ $activity->user?->name ?? __('System') }}</div>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Details') }}</h3></div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('Requester') }}</div>
                            <div class="datagrid-content d-flex align-items-center gap-2">
                                <x-avatar :user="$ticket->requester" size="xs" />
                                <div>{{ $ticket->requester->name }}@if ($ticket->requester->department)<div class="text-secondary small">{{ $ticket->requester->department->name }}</div>@endif</div>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('Assignee') }}</div>
                            <div class="datagrid-content">
                                @if ($ticket->assignee)
                                    <div class="d-flex align-items-center gap-2"><x-avatar :user="$ticket->assignee" size="xs" /> {{ $ticket->assignee->name }}</div>
                                @else
                                    <span class="text-secondary fst-italic">{{ __('Unassigned') }}</span>
                                @endif
                            </div>
                        </div>
                        @if ($ticket->team)
                            <div class="datagrid-item">
                                <div class="datagrid-title">{{ __('Team') }}</div>
                                <div class="datagrid-content"><span class="badge bg-{{ $ticket->team->color }}-lt"><i class="ti ti-users-group"></i> {{ $ticket->team->name }}</span></div>
                            </div>
                        @endif
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('Created') }}</div>
                            <div class="datagrid-content">{{ $ticket->created_at->forViewer()->translatedFormat('j M Y, H:i') }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('Last update') }}</div>
                            <div class="datagrid-content">{{ $ticket->updated_at->diffForHumans() }}</div>
                        </div>
                        @if ($ticket->resolved_at)
                            <div class="datagrid-item">
                                <div class="datagrid-title">{{ __('Resolved') }}</div>
                                <div class="datagrid-content">{{ $ticket->resolved_at->forViewer()->translatedFormat('j M Y, H:i') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($user->isStaff())
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Service level') }}</h3>
                        @if ($ticket->sla_paused_at)
                            <div class="card-actions"><span class="badge bg-purple-lt"><i class="ti ti-player-pause"></i> {{ __('Paused') }}</span></div>
                        @endif
                    </div>
                    <div class="card-body">
                        @if ($ticket->resolution_due_at)
                            @if ($ticket->slaPolicy)
                                <div class="text-secondary small mb-3">{{ __('Policy') }}: <strong class="text-body">{{ $ticket->slaPolicy->name }}</strong></div>
                            @endif
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ __('First response') }}</span>
                                    <x-sla-badge :status="$ticket->firstResponseSlaStatus()" :due="$ticket->first_response_due_at" />
                                </div>
                                <div class="text-secondary small">
                                    {{ __('Due :date', ['date' => $ticket->first_response_due_at->forViewer()->translatedFormat('j M, H:i')]) }}
                                    @if ($ticket->first_responded_at) · {{ __('responded :date', ['date' => $ticket->first_responded_at->forViewer()->translatedFormat('j M, H:i')]) }} @endif
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ __('Resolution') }}</span>
                                    <x-sla-badge :status="$ticket->resolutionSlaStatus()" :due="$ticket->resolution_due_at" />
                                </div>
                                <div class="text-secondary small">{{ __('Due :date', ['date' => $ticket->resolution_due_at->forViewer()->translatedFormat('j M, H:i')]) }}</div>
                            </div>
                        @else
                            <div class="text-secondary">{{ __('No SLA policy applies to this ticket.') }}</div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($canUpdate || $canAssign)
                <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="card mt-3">
                    @csrf
                    @method('PATCH')
                    <div class="card-header"><h3 class="card-title">{{ __('Manage ticket') }}</h3></div>
                    <div class="card-body">
                        @if ($canUpdate)
                            <div class="mb-3">
                                <label class="form-label" for="status">{{ __('Status') }}</label>
                                <select id="status" name="status" class="form-select">
                                    @foreach (TicketStatus::cases() as $status)
                                        @if ($ticket->status->canTransitionTo($status))
                                            <option value="{{ $status->value }}" @selected($ticket->status === $status)>{{ $status->label() }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="priority">{{ __('Priority') }}</label>
                                <select id="priority" name="priority" class="form-select">
                                    @foreach (App\Enums\TicketPriority::cases() as $priority)
                                        <option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>{{ $priority->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="category_id">{{ __('Category') }}</label>
                                <select id="category_id" name="category_id" class="form-select">
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected($ticket->category_id === $category->id)>{{ __($category->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @if ($canAssign && $teams->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label" for="team_id">{{ __('Team') }}</label>
                                <select id="team_id" name="team_id" class="form-select">
                                    <option value="">{{ __('No team') }}</option>
                                    @foreach ($teams as $team)
                                        <option value="{{ $team->id }}" @selected($ticket->team_id === $team->id)>{{ $team->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @if ($canAssign)
                            <div>
                                <label class="form-label" for="assignee_id">{{ __('Assignee') }}</label>
                                <select id="assignee_id" name="assignee_id" class="form-select">
                                    <option value="">{{ __('Unassigned') }}</option>
                                    @foreach ($agents as $agent)
                                        <option value="{{ $agent->id }}" @selected($ticket->assignee_id === $agent->id)>{{ $agent->name }}{{ $agent->is($user) ? ' ('.__('me').')' : '' }}{{ $ticket->team_id && $agent->teams->contains('id', $ticket->team_id) ? ' · '.$ticket->team->name : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100"><i class="ti ti-device-floppy icon"></i> {{ __('Save changes') }}</button>
                    </div>
                </form>
            @endif

            @if ($ticket->escalations->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-header"><h3 class="card-title">{{ __('Escalations') }}</h3></div>
                    <div class="list-group list-group-flush">
                        @foreach ($ticket->escalations->sortByDesc('id') as $escalation)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ __('Level :level', ['level' => $escalation->level]) }}</strong>
                                    <span class="badge {{ $escalation->type === App\Enums\EscalationType::Automatic ? 'bg-orange-lt' : 'bg-blue-lt' }}">{{ $escalation->type->label() }}</span>
                                </div>
                                <div class="small mt-1">{{ $escalation->reason }}</div>
                                <div class="text-secondary small mt-1">
                                    {{ $escalation->escalatedBy?->name ?? __('System') }}
                                    @if ($escalation->escalatedTo) → {{ $escalation->escalatedTo->name }} @endif
                                    · {{ $escalation->created_at->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @can('escalate', $ticket)
        <div class="modal modal-blur fade" id="modal-escalate" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form method="POST" action="{{ route('tickets.escalations.store', $ticket) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Escalate :reference', ['reference' => $ticket->reference]) }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            {{ __('The ticket moves to escalation level :level and its priority is raised to :priority.', ['level' => $ticket->escalation_level + 1, 'priority' => $ticket->priority->raised()->label()]) }}
                        </div>
                        <div class="mb-3">
                            <label class="form-label required" for="reason">{{ __('Reason') }}</label>
                            <textarea id="reason" name="reason" rows="3" required class="form-control" placeholder="{{ __('Why does this ticket need more attention?') }}">{{ old('reason') }}</textarea>
                        </div>
                        <div>
                            <label class="form-label" for="escalate_to_id">{{ __('Hand over to') }} <span class="form-label-description">{{ __('Optional') }}</span></label>
                            <select id="escalate_to_id" name="escalate_to_id" class="form-select">
                                <option value="">{{ __('Keep current assignee') }}</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}">{{ $agent->name }} · {{ __($agent->role->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger ms-auto"><i class="ti ti-arrow-big-up-lines icon"></i> {{ __('Escalate') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
</x-layouts.app>
