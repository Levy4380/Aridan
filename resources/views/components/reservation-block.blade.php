@props([
    'block',
    'highlighted' => false,
])

{{--
    A reservation on the calendar: the same 3 lines as the spreadsheet
    (arquitectura-frontend.md, 5.1). Painted with the responsible employee's
    color. A clipped side (the stay continues outside the window) is square
    and shows a chevron.
--}}
<button
    type="button"
    wire:click="$dispatch('open-reservation', { id: {{ $block->reservationId }} })"
    {{ $attributes->except('style')->class([
        'relative z-10 my-[5px] flex min-w-0 cursor-pointer flex-col items-stretch justify-center overflow-hidden border text-left hover:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-accent border-[rgba(20,22,26,0.22)] px-1.5 leading-[15px]',
        'rounded-l-[3px]' => ! $block->clipLeft,
        'rounded-r-[3px]' => ! $block->clipRight,
        'reservation-highlight' => $highlighted,
    ]) }}
    @if ($highlighted) x-init="$el.scrollIntoView({ block: 'center', inline: 'center' })" @endif
    style="{{ $attributes->get('style') }} background-color: {{ $block->color }}; color: {{ $block->textColor }};"
    aria-label="{{ $block->ariaLabel }}"
    title="{{ $block->ariaLabel }}"
>
    <div class="flex min-w-0 items-center gap-0.5 text-xs font-bold">
        @if ($block->clipLeft)
            <flux:icon.chevron-left variant="micro" class="size-3 shrink-0" />
        @endif
        @if ($block->earlyCheckIn)
            <x-icon.sunrise class="size-3 shrink-0" />
        @endif
        <span class="truncate">{{ $block->guestName }}</span>
        @if ($block->lateCheckOut)
            <flux:icon.moon variant="micro" class="size-3 shrink-0" />
        @endif
        @if ($block->clipRight)
            <flux:icon.chevron-right variant="micro" class="ms-auto size-3 shrink-0" />
        @endif
    </div>
    <div class="truncate text-[11px] font-medium">{{ $block->peopleLine }}</div>
    <div class="flex min-w-0 items-center gap-1 text-[11px]">
        @unless ($block->isMovable)
            <flux:icon.lock-closed variant="micro" class="size-3 shrink-0" />
        @endunless
        <span class="truncate">{{ $block->employeeName }}@unless ($block->isMovable) · {{ __('calendar.fixed') }}@endunless</span>
        @foreach ($block->extras as $extra)
            @if ($extra['icon'])
                <x-dynamic-component :component="'icon.'.$extra['icon']" class="size-3 shrink-0" :title="$extra['name']" />
            @else
                <span class="shrink-0">· {{ $extra['name'] }}</span>
            @endif
        @endforeach
    </div>
</button>
