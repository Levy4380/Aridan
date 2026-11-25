@php
    $columns = '132px repeat('.$grid->slotCount().', calc(var(--day-w) / 2))';
@endphp

<div class="flex flex-col gap-3" style="--day-w: {{ $range === \App\Livewire\Calendar\Index::RANGE_TWO_WEEKS ? '88px' : '40px' }}">
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1">
            <flux:button square variant="ghost" icon="chevron-left" wire:click="previous" :aria-label="__('calendar.previous')" />
            <flux:button square variant="ghost" icon="chevron-right" wire:click="next" :aria-label="__('calendar.next')" />
        </div>
        <h1 class="min-w-44 text-[26px] font-bold leading-tight tracking-[-0.01em] text-ink">{{ $title }}</h1>
        <flux:button size="sm" wire:click="today">{{ __('calendar.today') }}</flux:button>

        <flux:radio.group wire:model.live="range" variant="segmented" size="sm" class="w-auto">
            <flux:radio value="{{ \App\Livewire\Calendar\Index::RANGE_MONTH }}" :label="__('calendar.range_month')" />
            <flux:radio value="{{ \App\Livewire\Calendar\Index::RANGE_TWO_WEEKS }}" :label="__('calendar.range_two_weeks')" />
        </flux:radio.group>

        <p class="text-[13px] text-subtle">
            {{ trans_choice('calendar.summary_apartments', $grid->activeApartments) }} · {{ trans_choice('calendar.summary_reservations', $grid->reservationCount) }}
        </p>

        @if (Route::has('availability'))
            <div class="ms-auto flex gap-2">
                <flux:button :href="route('availability')" wire:navigate icon="magnifying-glass">{{ __('calendar.search') }}</flux:button>
                <flux:button :href="route('availability')" wire:navigate variant="primary" icon="plus">{{ __('calendar.new_reservation') }}</flux:button>
            </div>
        @endif
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs font-semibold text-muted">
        <span>{{ __('calendar.legend_responsible') }}</span>
        @foreach ($grid->employees as $employee)
            <span class="flex items-center gap-1.5 font-medium text-ink-2">
                <span class="size-3 rounded-[2px] border border-[rgba(20,22,26,0.2)]" style="background-color: {{ $employee->color }}"></span>
                {{ $employee->name }}
            </span>
        @endforeach
        <span class="h-4 w-px bg-divider" aria-hidden="true"></span>
        <span class="flex items-center gap-1 font-medium"><flux:icon.lock-closed variant="micro" class="size-3.5" /> {{ __('calendar.legend_fixed') }}</span>
        <span class="flex items-center gap-1 font-medium"><x-icon.sunrise class="size-3.5" /> {{ __('calendar.legend_early') }}</span>
        <span class="flex items-center gap-1 font-medium"><flux:icon.moon variant="micro" class="size-3.5" /> {{ __('calendar.legend_late') }}</span>
        <span class="flex items-center gap-1 font-medium"><x-icon.parking class="size-3.5" /> {{ __('calendar.legend_parking') }}</span>
        <span class="flex items-center gap-1 font-medium"><x-icon.breakfast class="size-3.5" /> {{ __('calendar.legend_breakfast') }}</span>
    </div>

    {{-- Grid --}}
    <div class="overflow-x-auto rounded-ui border border-border bg-surface" wire:loading.class="opacity-60">
        <div class="w-max min-w-full">
            {{-- Header: weekday, day number, holiday, M/T --}}
            <div class="grid border-b border-divider bg-surface-alt" style="grid-template-columns: {{ $columns }}">
                <div class="sticky left-0 z-20 row-span-2 flex items-end border-r border-divider bg-surface-alt px-3 pb-1.5 text-xs font-semibold text-muted">
                    {{ __('calendar.apartment_column') }}
                </div>
                @foreach ($grid->days as $day)
                    <div
                        @class([
                            'col-span-2 flex flex-col items-center border-l border-divider pt-1.5 pb-0.5',
                            'bg-weekend-header' => $day->isWeekend && ! $day->isToday,
                            'bg-accent-soft' => $day->isToday,
                        ])
                        @if ($day->holiday) title="{{ __('calendar.holiday', ['name' => $day->holiday]) }}" @endif
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
                    class="grid h-[62px] border-b border-divider-soft"
                    style="grid-template-columns: {{ $columns }}; grid-template-rows: 62px"
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
                            wire:key="block-{{ $block->reservationId }}"
                            style="grid-row: 1; grid-column: {{ $block->startColumn + 1 }} / {{ $block->endColumn + 1 }};"
                        />
                    @endforeach
                </div>
            @endforeach

            {{-- Free apartments per night --}}
            <div class="grid bg-surface-alt" style="grid-template-columns: {{ $columns }}">
                <div class="sticky left-0 z-20 border-r border-divider bg-surface-alt px-3 py-2 text-xs font-semibold text-muted">
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
