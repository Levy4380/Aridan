@props([
    'stay',   // App\Domain\StayRange
    'people',
])

@php
    $day = fn ($date, bool $morning) => $date->locale('es')->isoFormat('ddd').' '.\App\Support\Format::date($date)
        .' ('.__($morning ? 'reservations.half.morning' : 'reservations.half.afternoon').')';
@endphp

{{-- What the search fixed (arquitectura-frontend.md, 4.2), with a "Cambiar" action slot. --}}
<div {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-ui bg-canvas p-3']) }}>
    <flux:icon.calendar class="size-5 shrink-0 text-muted" />
    <div class="min-w-0 flex-1">
        <p class="font-semibold text-ink">{{ $day($stay->checkIn, $stay->earlyCheckIn) }} → {{ $day($stay->checkOut, ! $stay->lateCheckOut) }}</p>
        <p class="text-[13px] text-subtle">
            {{ trans_choice('availability.nights', $stay->nights(), ['count' => $stay->nights()]) }}
            · {{ trans_choice('reservations.people_long', $people, ['count' => $people]) }}
            @if ($stay->earlyCheckIn) · {{ __('availability.early_check_in') }} @endif
            @if ($stay->lateCheckOut) · {{ __('availability.late_check_out') }} @endif
        </p>
    </div>
    {{ $slot }}
</div>
