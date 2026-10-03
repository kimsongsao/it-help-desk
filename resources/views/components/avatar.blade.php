@props(['user' => null, 'size' => null])

@php
    $colors = ['blue', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green', 'teal', 'cyan'];
    $initials = $user
        ? str($user->name)->explode(' ')->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('')
        : null;
    $color = $user ? $colors[crc32($user->name) % count($colors)] : 'secondary';
@endphp

<span {{ $attributes->class(['avatar', "avatar-{$size}" => $size, "bg-{$color}-lt"]) }} @if ($user) title="{{ $user->name }}" @endif>
    @if ($user)
        {{ mb_strtoupper($initials) }}
    @else
        <i class="ti ti-robot"></i>
    @endif
</span>
