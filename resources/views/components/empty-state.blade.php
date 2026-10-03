@props(['icon' => 'ti-mood-empty', 'title', 'subtitle' => null])

<div {{ $attributes->class('empty py-5') }}>
    <div class="empty-icon"><i class="ti {{ $icon }} fs-1 text-secondary"></i></div>
    <p class="empty-title">{{ $title }}</p>
    @if ($subtitle)
        <p class="empty-subtitle text-secondary">{{ $subtitle }}</p>
    @endif
    @isset($action)
        <div class="empty-action">{{ $action }}</div>
    @endisset
</div>
