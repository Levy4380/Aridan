@props([
    'guest', // App\Models\Guest, ideally with reservations_count
])

@php
    $initials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($guest->first_name, 0, 1).\Illuminate\Support\Str::substr($guest->last_name, 0, 1));
    $count = $guest->reservations_count ?? null;
@endphp

{{-- Guest summary (arquitectura-frontend.md, 4.2) with an action slot. --}}
<div {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-ui border border-border-control bg-surface p-3']) }}>
    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-ink text-sm font-bold text-white" aria-hidden="true">{{ $initials }}</span>
    <div class="min-w-0 flex-1">
        <p class="truncate font-semibold text-ink">{{ $guest->full_name }}</p>
        <p class="text-[13px] text-subtle sm:truncate">
            {{ __('guests.card.phone', ['phone' => \App\Support\Format::phone($guest->phone)]) }}
            · {{ $guest->document_number ? __('guests.card.document', ['document' => \App\Support\Format::document($guest->document_number)]) : __('guests.card.no_document') }}
            @if ($count !== null)
                · {{ trans_choice('guests.card.reservations', $count, ['count' => $count]) }}
            @endif
        </p>
    </div>
    {{ $slot }}
</div>
