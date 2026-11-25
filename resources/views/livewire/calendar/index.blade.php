@php
    // Days stretch to use the whole width, never narrower than --day-w.
    $dayWidth = $range === \App\Livewire\Calendar\Index::RANGE_TWO_WEEKS ? 96 : 52;
    $slots = $grid->slotCount();
@endphp

<div
    class="calendar flex flex-col gap-3"
    style="--day-w: {{ $dayWidth }}px; --slots: {{ $slots }}; --calendar-min-w: calc(var(--apartment-w) + {{ $slots }} * var(--day-w) / 2);"
>
    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" role="status" data-test="status-message" class="print:hidden">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Toolbar (not printed). On a phone: period + Hoy, then the range, then the actions. --}}
    <div class="flex flex-wrap items-center gap-x-3 gap-y-2.5 print:hidden">
        <div class="flex items-center gap-x-2 max-sm:w-full sm:gap-x-3">
            <div class="flex shrink-0 items-center sm:gap-1">
                <flux:button square variant="ghost" icon="chevron-left" wire:click="previous" :aria-label="__('calendar.previous')" />
                <flux:button square variant="ghost" icon="chevron-right" wire:click="next" :aria-label="__('calendar.next')" />
            </div>
            <h1 class="min-w-0 truncate text-[18px] font-bold leading-tight tracking-[-0.01em] text-ink max-sm:flex-1 sm:min-w-44 sm:text-[26px]">{{ $title }}</h1>
            <flux:button size="sm" wire:click="today" class="shrink-0">{{ __('calendar.today') }}</flux:button>
        </div>

        <flux:radio.group wire:model.live="range" variant="segmented" size="sm" class="w-auto">
            <flux:radio value="{{ \App\Livewire\Calendar\Index::RANGE_MONTH }}" :label="__('calendar.range_month')" />
            <flux:radio value="{{ \App\Livewire\Calendar\Index::RANGE_TWO_WEEKS }}" :label="__('calendar.range_two_weeks')" />
        </flux:radio.group>

        <p class="text-[13px] text-subtle">
            {{ trans_choice('calendar.summary_apartments', $grid->activeApartments) }} · {{ trans_choice('calendar.summary_reservations', $grid->reservationCount) }}
        </p>

        <div class="flex gap-2 max-sm:w-full sm:ms-auto">
            <flux:button icon="printer" wire:click="printMonth" class="shrink-0" :aria-label="__('calendar.print')" data-test="print-button"><span class="max-sm:hidden">{{ __('calendar.print') }}</span></flux:button>
            @if (Route::has('availability'))
                {{-- Searching and booking are the same flow: every reservation starts in the search. --}}
                <flux:button :href="route('availability')" wire:navigate variant="primary" icon="magnifying-glass" class="max-sm:flex-1" data-test="search-and-book"><span class="max-[359px]:hidden">{{ __('calendar.search_and_book') }}</span><span class="min-[360px]:hidden">{{ __('calendar.search_and_book_short') }}</span></flux:button>
            @endif
        </div>
    </div>

    {{-- Legend (not printed). On a phone it folds away so the grid starts higher. --}}
    <x-calendar.legend :employees="$grid->employees" class="print:hidden max-sm:hidden" />
    <details class="group rounded-ui border border-border bg-surface sm:hidden print:hidden">
        <summary class="flex list-none items-center justify-between px-3 py-2 text-xs font-semibold text-muted [&::-webkit-details-marker]:hidden">
            {{ __('calendar.legend') }}
            <flux:icon.chevron-down variant="micro" class="size-4 transition-transform group-open:rotate-180" />
        </summary>
        <x-calendar.legend :employees="$grid->employees" class="border-t border-divider px-3 py-2.5" />
    </details>

    {{-- Grid --}}
    {{--
        The grid scrolls sideways inside its card (edge to edge on a phone).
        On phones and tablets, on load and after "Hoy", it scrolls so
        yesterday is the first visible day (today's checkouts show):
        otherwise you always land on the 1st. Desktop keeps the month start.
    --}}
    <div
        class="calendar-scroll relative overflow-x-auto overscroll-x-contain rounded-ui border border-border bg-surface max-sm:-mx-4 max-sm:rounded-none max-sm:border-x-0 print:mx-0 print:overflow-visible print:rounded-none"
        wire:loading.class="opacity-60"
        x-data="{
            toToday() {
                if (! window.matchMedia('(width < 64rem)').matches) return;
                const today = this.$el.querySelector('[data-today]');
                const column = this.$el.querySelector('[data-apartment-column]');
                if (today) this.$el.scrollLeft = today.offsetLeft - today.offsetWidth - (column?.offsetWidth ?? 0);
            },
        }"
        @unless ($highlight) x-init="$nextTick(() => toToday())" @endunless
        x-on:calendar-today.window="$nextTick(() => toToday())"
    >
        <div class="calendar-sheet w-full">
            {{-- Header: weekday, day number, holiday, M/T --}}
            <div class="calendar-row grid border-b border-divider bg-surface-alt" style="grid-template-columns: var(--calendar-cols)">
                <div data-apartment-column class="sticky left-0 z-20 row-span-2 flex items-end border-r border-divider bg-surface-alt px-2 pb-1.5 text-xs font-semibold text-muted sm:px-3">
                    <span class="max-sm:hidden">{{ __('calendar.apartment_column') }}</span>
                    <span class="sm:hidden">{{ __('calendar.apartment_column_short') }}</span>
                </div>
                @foreach ($grid->days as $day)
                    <div
                        @class([
                            'col-span-2 flex flex-col items-center border-l border-divider pt-1.5 pb-0.5',
                            'bg-weekend-header' => $day->isWeekend && ! $day->isToday,
                            'bg-accent-soft' => $day->isToday,
                        ])
                        @if ($day->holiday) title="{{ __('calendar.holiday', ['name' => $day->holiday]) }}" @endif
                        @if ($day->isToday) data-today @endif
                    >
                        <span class="text-[10px] font-semibold uppercase text-faint">{{ \Illuminate\Support\Str::substr($day->date->locale('es')->minDayName, 0, 1) }}</span>
                        <span @class([
                            'mt-0.5 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[13px] font-bold',
                            'bg-accent text-white' => $day->isToday,
                            'text-ink' => ! $day->isToday,
                        ])>{{ $day->date->day }}</span>
                        <span class="flex h-2.5 items-center">
                            @if ($day->holiday)
                                <span class="size-1.5 rounded-full bg-holiday" aria-label="{{ __('calendar.holiday', ['name' => $day->holiday]) }}"></span>
                            @endif
                        </span>
                    </div>
                @endforeach
                @foreach ($grid->days as $day)
                    <span class="border-l border-divider text-center text-[9px] text-faint">M</span>
                    <span class="text-center text-[9px] text-faint">T</span>
                @endforeach
            </div>

            {{-- One row per apartment --}}
            @foreach ($grid->rows as $row)
                <div
                    wire:key="row-{{ $row->apartment->id }}"
                    class="calendar-row calendar-apartment-row grid h-[62px] border-b border-divider-soft"
                    style="grid-template-columns: var(--calendar-cols); grid-template-rows: 62px"
                >
                    <x-calendar.apartment-cell :apartment="$row->apartment" />

                    {{-- Day backgrounds --}}
                    @foreach ($grid->days as $i => $day)
                        <div
                            @class([
                                'border-l border-divider',
                                'bg-accent-softer' => $day->isToday,
                                'bg-weekend' => $day->isWeekend && ! $day->isToday,
                                'calendar-inactive' => ! $row->apartment->is_active,
                            ])
                            style="grid-row: 1; grid-column: {{ $i * 2 + 2 }} / span 2; background-image: linear-gradient(to right, transparent calc(50% - 0.5px), var(--color-divider-soft) calc(50% - 0.5px), var(--color-divider-soft) calc(50% + 0.5px), transparent calc(50% + 0.5px));"
                        ></div>
                    @endforeach

                    @foreach ($row->blocks as $block)
                        <x-reservation-block
                            :block="$block"
                            :highlighted="$block->reservationId === $highlight"
                            wire:key="block-{{ $block->reservationId }}"
                            style="grid-row: 1; grid-column: {{ $block->startColumn + 1 }} / {{ $block->endColumn + 1 }};"
                        />
                    @endforeach
                </div>
            @endforeach

            {{-- Free apartments per night --}}
            <div class="calendar-row grid bg-surface-alt" style="grid-template-columns: var(--calendar-cols)">
                <div class="sticky left-0 z-20 border-r border-divider bg-surface-alt px-2 py-2 text-xs font-semibold leading-tight text-muted sm:px-3">
                    {{ __('calendar.free_per_night') }}
                </div>
                @foreach ($grid->freeByNight as $i => $free)
                    <div @class([
                        'col-span-2 border-l border-divider py-2 text-center text-xs',
                        'font-bold text-danger' => $free <= 3,
                        'text-ink-2' => $free > 3,
                    ])>{{ $free }}</div>
                @endforeach
            </div>
        </div>
    </div>

</div>
