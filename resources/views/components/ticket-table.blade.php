@props(['tickets', 'showRequester' => true, 'showSla' => true, 'empty' => null])

<div class="table-responsive">
    <table class="table table-vcenter table-hover card-table">
        <thead>
            <tr>
                <th>{{ __('Ticket') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Priority') }}</th>
                @if ($showSla)
                    <th>{{ __('SLA') }}</th>
                @endif
                @if ($showRequester)
                    <th>{{ __('Requester') }}</th>
                @endif
                <th>{{ __('Assignee') }}</th>
                <th>{{ __('Updated') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                <tr data-href="{{ route('tickets.show', $ticket) }}">
                    <td>
                        <a href="{{ route('tickets.show', $ticket) }}" class="text-reset fw-medium">{{ $ticket->subject }}</a>
                        <div class="text-secondary small">
                            {{ $ticket->reference }} · {{ $ticket->category?->name ? __($ticket->category->name) : __('Uncategorized') }}
                            @if ($ticket->escalation_level > 0)
                                <span class="badge bg-red-lt ms-1" title="{{ __('Escalation level') }}"><i class="ti ti-arrow-big-up-lines"></i> L{{ $ticket->escalation_level }}</span>
                            @endif
                        </div>
                    </td>
                    <td><x-badge :value="$ticket->status" /></td>
                    <td><x-badge :value="$ticket->priority" /></td>
                    @if ($showSla)
                        <td><x-sla-badge :status="$ticket->resolutionSlaStatus()" :due="$ticket->resolution_due_at" /></td>
                    @endif
                    @if ($showRequester)
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <x-avatar :user="$ticket->requester" size="xs" />
                                <span class="text-secondary">{{ $ticket->requester->name }}</span>
                            </div>
                        </td>
                    @endif
                    <td>
                        @if ($ticket->assignee)
                            <div class="d-flex align-items-center gap-2">
                                <x-avatar :user="$ticket->assignee" size="xs" />
                                <span class="text-secondary">{{ $ticket->assignee->name }}</span>
                            </div>
                        @else
                            <span class="text-secondary fst-italic">{{ __('Unassigned') }}</span>
                        @endif
                    </td>
                    <td class="text-secondary text-nowrap" title="{{ $ticket->updated_at->translatedFormat('j M Y, H:i') }}">{{ $ticket->updated_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        @if ($empty)
                            {{ $empty }}
                        @else
                            <x-empty-state icon="ti-ticket-off" :title="__('No tickets found')" :subtitle="__('Try changing the filters or search terms.')" />
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
