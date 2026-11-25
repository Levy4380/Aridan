@props([
    'timing', // upcoming | current | past | cancelled (Reservation::timing())
])

{{-- Próxima / En curso / Pasada / Cancelada (arquitectura-frontend.md, 4.2). --}}
<span {{ $attributes->class([
    'inline-flex items-center whitespace-nowrap rounded-[3px] px-2 py-0.5 text-xs font-bold',
    'bg-accent-soft text-accent' => $timing === 'upcoming',
    'bg-success-bg text-success-fg' => $timing === 'current',
    'bg-chip text-subtle' => $timing === 'past',
    'bg-danger-bg text-danger-fg' => $timing === 'cancelled',
]) }}>{{ __('reservations.timing.'.$timing) }}</span>
