@props([
    'active',
])

{{-- Active / inactive badge (arquitectura-frontend.md, 4.2). --}}
<span {{ $attributes->class([
    'inline-flex items-center rounded-[3px] px-2 py-0.5 text-xs font-bold',
    'bg-success-bg text-success-fg' => $active,
    'bg-chip text-subtle' => ! $active,
]) }}>{{ $active ? __('common.active') : __('common.inactive') }}</span>
