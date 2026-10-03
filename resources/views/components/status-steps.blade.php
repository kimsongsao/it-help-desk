@props(['ticket'])

@php
    use App\Enums\TicketStatus;

    $steps = [
        ['label' => __('Submitted'), 'done' => true],
        ['label' => __('Being worked on'), 'done' => in_array($ticket->status, [TicketStatus::InProgress, TicketStatus::OnHold, TicketStatus::Resolved, TicketStatus::Closed], true)],
        ['label' => __('Resolved'), 'done' => in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true)],
        ['label' => __('Closed'), 'done' => $ticket->status === TicketStatus::Closed],
    ];
    $current = collect($steps)->filter(fn ($step) => $step['done'])->keys()->last();
@endphp

<div {{ $attributes->class('steps steps-counter steps-green my-2') }}>
    @foreach ($steps as $index => $step)
        <span @class(['step-item', 'active' => $index === $current])>{{ $step['label'] }}</span>
    @endforeach
</div>
