@props(['status', 'due' => null])

@if ($status)
    <span {{ $attributes->class(['badge', "bg-{$status->color()}-lt"]) }} @if ($due) title="{{ __('Due :date', ['date' => $due->forViewer()->translatedFormat('j M Y, H:i')]) }}" @endif>
        {{ $status->label() }}@if ($due && in_array($status, [App\Enums\SlaStatus::OnTrack, App\Enums\SlaStatus::AtRisk], true)) · {{ $due->diffForHumans(['parts' => 1, 'short' => true]) }}@endif
    </span>
@else
    <span class="text-secondary">—</span>
@endif
