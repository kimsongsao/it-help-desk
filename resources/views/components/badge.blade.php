@props(['value'])

<span {{ $attributes->class(['badge', "bg-{$value->color()}-lt"]) }}>{{ $value->label() }}</span>
