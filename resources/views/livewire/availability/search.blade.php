@php
    $columns = '36px 96px 150px 210px max-content 1fr';
@endphp

<div class="mx-auto flex w-full max-w-[1280px] flex-col gap-4">
    @if ($editing)
        <x-page-header :title="__('availability.edit.title')" :description="__('availability.edit.description')" />

        <flux:callout variant="secondary" icon="pencil-square" data-test="editing-banner">
            <flux:callout.text>
                {{ __('availability.edit.banner', [
                    'guest' => $editing->guest->full_name,
                    'number' => $editing->apartment->number,
                    'from' => \App\Support\Format::date($editing->check_in_date),
                    'to' => \App\Support\Format::date($editing->check_out_date),
                ]) }}
            </flux:callout.text>
            <x-slot name="actions">
                <flux:button size="sm" variant="ghost" :href="route('calendar', ['desde' => $editing->check_in_date->startOfMonth()->toDateString(), 'reserva' => $editing->id])" wire:navigate>
                    {{ __('availability.edit.back') }}
                </flux:button>
            </x-slot>
        </flux:callout>
    @else
        <x-page-header :title="__('availability.title')" :description="__('availability.description')" />
    @endif

    @if ($forGuest && ! $editing)
        <flux:callout variant="secondary" icon="user" data-test="for-guest">
            <flux:callout.text>{{ __('availability.for_guest', ['name' => $forGuest->full_name]) }}</flux:callout.text>
            <x-slot name="actions">
                <flux:button size="sm" variant="ghost" wire:click="$set('guestId', null)">{{ __('availability.for_guest_clear') }}</flux:button>
            </x-slot>
        </flux:callout>
    @endif

    {{-- Search form: no "Buscar" button, results follow the fields. --}}
    <div class="flex flex-wrap items-end gap-x-5 gap-y-3 rounded-ui border border-border bg-surface p-4">
        <flux:input type="date" wire:model.live="checkIn" :label="__('availability.check_in')" class="w-40" />
        <flux:input type="date" wire:model.live="checkOut" :label="__('availability.check_out')" class="w-40" />
        <flux:input type="number" min="1" max="30" wire:model.live.debounce.400ms="people" :label="__('availability.people')" class="w-[90px]" />
        <div class="flex flex-col gap-2 pb-1">
            <flux:checkbox wire:model.live="earlyCheckIn" :label="__('availability.early_check_in')" />
            <flux:checkbox wire:model.live="lateCheckOut" :label="__('availability.late_check_out')" />
        </div>

        @if ($stay)
            <p class="ms-auto pb-2 text-sm text-ink-2" role="status">
                <span class="font-bold text-ink">{{ trans_choice('availability.nights', $stay->nights(), ['count' => $stay->nights()]) }}</span>
                · {{ __('availability.summary_range', [
                    'from' => $stay->checkIn->locale('es')->isoFormat('ddd').' '.\App\Support\Format::date($stay->checkIn),
                    'to' => $stay->checkOut->locale('es')->isoFormat('ddd').' '.\App\Support\Format::date($stay->checkOut),
                ]) }}
                @if ($peopleCount)
                    · {{ \App\Support\Format::people($peopleCount) }}
                @endif
            </p>
        @endif
    </div>

    @if (! $stay)
        <flux:callout variant="danger" icon="exclamation-circle">
            <flux:callout.text>{{ __('availability.invalid_dates') }}</flux:callout.text>
        </flux:callout>
    @elseif (! $peopleCount)
        {{-- Not an error: the field may be empty while typing. Just wait. --}}
        <div class="rounded-ui border border-border bg-surface p-6 text-center text-sm text-subtle" role="status" data-test="people-prompt">
            {{ __('availability.people_prompt') }}
        </div>
    @else
        <div class="flex flex-col gap-4" wire:loading.class="opacity-60">
            {{-- Edit mode: short confirmation of the change --}}
            @if ($confirmApartment)
                <div role="alertdialog" aria-labelledby="confirm-change-title" class="flex flex-col gap-3 rounded-ui border-2 border-accent bg-accent-softer p-4" data-test="confirm-change">
                    <p id="confirm-change-title" class="text-[17px] font-bold text-ink">
                        {{ __('availability.edit.confirm', [
                            'guest' => $editing->guest->full_name,
                            'number' => $confirmApartment->number,
                            'from' => \App\Support\Format::date($stay->checkIn),
                            'to' => \App\Support\Format::date($stay->checkOut),
                            'people' => \App\Support\Format::people($peopleCount),
                        ]) }}
                    </p>
                    @foreach ($changeWarnings as $warning)
                        <flux:callout variant="warning" icon="exclamation-triangle">
                            <flux:callout.text>{{ $warning->message }}</flux:callout.text>
                        </flux:callout>
                    @endforeach
                    @error('reservation')
                        <flux:callout variant="danger" icon="x-circle" role="alert">
                            <flux:callout.text>{{ $message }}</flux:callout.text>
                        </flux:callout>
                    @enderror
                    <div class="flex justify-end gap-2">
                        <flux:button variant="filled" wire:click="cancelChange">{{ __('availability.edit.cancel') }}</flux:button>
                        <flux:button variant="primary" wire:click="applyChange" data-test="apply-change-button">
                            {{ $changeWarnings ? __('reservations.create.save_anyway') : __('availability.edit.apply') }}
                        </flux:button>
                    </div>
                </div>
            @endif

            {{-- Group 1: available --}}
            <section class="flex flex-col gap-2" aria-labelledby="available-heading">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="available-heading" class="text-[17px] font-bold text-ink">{{ __('availability.available', ['count' => count($suggestions->available)]) }}</h2>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                        <span class="flex items-center gap-1"><span class="size-3 rounded-[2px] bg-occ-fixed"></span>{{ __('availability.legend.fixed') }}</span>
                        <span class="flex items-center gap-1"><span class="size-3 rounded-[2px] bg-occ-movable"></span>{{ __('availability.legend.movable') }}</span>
                        <span class="flex items-center gap-1"><span class="size-3 rounded-[2px] bg-occ-free"></span>{{ __('availability.legend.free') }}</span>
                        <span class="flex items-center gap-1"><span class="size-3 rounded-[2px] bg-occ-requested outline-2 -outline-offset-2 outline-occ-requested-border"></span>{{ __('availability.legend.requested') }}</span>
                        <span class="text-faint">{{ __('availability.legend.help') }}</span>
                    </div>
                </div>

                @if ($suggestions->available === [])
                    <div class="rounded-ui border border-border bg-surface p-6 text-center text-sm text-subtle">
                        {{ __('availability.none_available') }}
                    </div>
                @else
                    <div class="overflow-x-auto rounded-ui border border-border bg-surface">
                        <div class="min-w-[1180px]">
                            <div class="grid items-end gap-x-3 border-b border-divider bg-surface-alt px-3 py-2 text-xs font-semibold text-muted" style="grid-template-columns: {{ $columns }}">
                                <span>#</span>
                                <span>{{ __('availability.columns.apartment') }}</span>
                                <span>{{ __('availability.columns.capacity') }}</span>
                                <span>{{ __('availability.columns.gap') }}</span>
                                <div class="flex gap-0.5" aria-hidden="true">
                                    @foreach ($mapDays as $day)
                                        <span @class([
                                            'w-[26px] shrink-0 text-center text-[9px] leading-tight',
                                            'font-bold text-occ-requested-text' => $day['requested'],
                                            'font-normal text-faint' => ! $day['requested'],
                                        ])>{{ $day['date']->format('d') }}<br>{{ \Illuminate\Support\Str::substr($day['date']->locale('es')->minDayName, 0, 1) }}</span>
                                    @endforeach
                                </div>
                                <span></span>
                            </div>

                            @foreach ($suggestions->available as $option)
                                @php($apartment = $option->availability->apartment)
                                <div
                                    wire:key="available-{{ $apartment->id }}"
                                    @class([
                                        'grid items-center gap-x-3 border-b border-divider-soft px-3 py-2 last:border-0',
                                        'bg-accent-best' => $option->isBest(),
                                    ])
                                    style="grid-template-columns: {{ $columns }}"
                                >
                                    <span class="text-sm text-faint">{{ $option->rank }}</span>
                                    <span class="flex items-baseline gap-1.5">
                                        <span class="text-[19px] font-bold text-ink">{{ $apartment->number }}</span>
                                        <span class="text-xs text-subtle">{{ \App\Support\Format::people($apartment->capacity) }}</span>
                                    </span>
                                    <span class="flex flex-wrap gap-1">
                                        <x-fit-badge :fit="$option->fit" />
                                        @if ($option->isBest())
                                            <span class="inline-flex items-center rounded-[3px] bg-accent px-2 py-0.5 text-xs font-bold text-white">{{ __('availability.best') }}</span>
                                        @endif
                                    </span>
                                    <x-availability.gap :availability="$option->availability" class="text-[13px] text-ink-2" />
                                    <x-occupancy-map :days="$option->availability->map" :label="__('availability.map.label', ['number' => $apartment->number])" />
                                    <span class="flex items-center justify-end gap-2">
                                        @if ($editing)
                                            @if ($apartment->id === $editing->apartment_id)
                                                <span class="text-xs font-bold text-accent">{{ __('availability.edit.current') }}</span>
                                            @endif
                                            <flux:button
                                                size="sm"
                                                :variant="$option->isBest() ? 'primary' : 'filled'"
                                                wire:click="chooseForEditing({{ $apartment->id }})"
                                                data-test="use-{{ $apartment->number }}"
                                            >{{ __('availability.edit.use') }}</flux:button>
                                        @else
                                        <flux:button
                                            size="sm"
                                            :variant="$option->isBest() ? 'primary' : 'filled'"
                                            wire:click="$dispatch('open-reservation-form', {{ json_encode([
                                                'checkIn' => $stay->checkIn->toDateString(),
                                                'checkOut' => $stay->checkOut->toDateString(),
                                                'early' => $stay->earlyCheckIn,
                                                'late' => $stay->lateCheckOut,
                                                'people' => $peopleCount,
                                                'apartmentId' => $apartment->id,
                                                'guestId' => $forGuest?->id,
                                            ]) }})"
                                            data-test="reserve-{{ $apartment->number }}"
                                        >{{ __('availability.reserve') }}</flux:button>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            {{-- Group 2: occupied only by movable reservations --}}
            @if ($suggestions->movable !== [])
                <section class="flex flex-col gap-2" aria-labelledby="movable-heading">
                    <div>
                        <h2 id="movable-heading" class="text-[17px] font-bold text-ink">{{ __('availability.movable', ['count' => count($suggestions->movable)]) }}</h2>
                        <p class="text-[13px] text-subtle">{{ __('availability.movable_help') }}</p>
                    </div>
                    <div class="rounded-ui border border-border bg-surface">
                        @foreach ($suggestions->movable as $item)
                            <div wire:key="movable-{{ $item->apartment->id }}" class="flex flex-wrap items-baseline gap-x-3 gap-y-1 border-b border-divider-soft px-3 py-2 last:border-0">
                                <span class="text-[17px] font-bold text-ink">{{ $item->apartment->number }}</span>
                                <span class="text-xs text-subtle">{{ \App\Support\Format::people($item->apartment->capacity) }}</span>
                                <span class="text-sm text-ink-2">
                                    {{ collect($item->conflicts)->map(fn ($r) => __('availability.occupied_by', [
                                        'guest' => $r->guest->full_name,
                                        'from' => \App\Support\Format::date($r->check_in_date),
                                        'to' => \App\Support\Format::date($r->check_out_date),
                                    ]))->join(' · ') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Group 3: not available --}}
            @if ($suggestions->unavailable !== [])
                <section class="flex flex-col gap-2" aria-labelledby="unavailable-heading">
                    <h2 id="unavailable-heading" class="text-[17px] font-bold text-ink">{{ __('availability.unavailable', ['count' => count($suggestions->unavailable)]) }}</h2>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($suggestions->unavailable as $item)
                            <span wire:key="unavailable-{{ $item->apartment->id }}" class="rounded-[3px] bg-chip px-2 py-1 text-xs font-semibold text-subtle">
                                {{ $item->apartment->number }} · {{ $item->apartment->is_active ? __('availability.reason_occupied') : __('availability.reason_inactive') }}
                            </span>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif

    <livewire:reservations.create-form />
</div>
