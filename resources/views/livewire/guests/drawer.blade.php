@php
    use App\Support\Format;
@endphp

<div>
    <flux:modal variant="flyout" wire:model.self.live="show" class="w-full max-md:max-w-none max-md:p-5 md:w-[560px]" data-test="guest-drawer">
        <div class="flex flex-col gap-5">
            <div class="pe-8">
                <h2 class="text-[20px] font-bold text-ink">{{ $this->guest?->full_name ?? __('guests.new') }}</h2>
                @if ($this->guest)
                    <p class="text-[13px] text-subtle">{{ __('guests.since', ['date' => ucfirst($this->guest->created_at->locale('es')->isoFormat('MMM YYYY'))]) }}</p>
                @endif
            </div>

            <form wire:submit="save" class="flex flex-col gap-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:input wire:model="form.first_name" :label="__('reservations.fields.first_name')" required />
                    <flux:input wire:model="form.last_name" :label="__('reservations.fields.last_name')" required />
                    <flux:input wire:model="form.phone" :label="__('reservations.fields.phone')" type="tel" required />
                    <flux:input wire:model="form.document_number" :label="__('reservations.fields.document')" :badge="__('guests.optional')" />
                </div>

                @if ($creating)
                    <p class="text-[13px] text-subtle">{{ __('guests.new_help') }}</p>
                @endif

                <div class="flex flex-wrap justify-end gap-2">
                    @if ($this->guest && Route::has('availability'))
                        <flux:button :href="route('availability', ['cliente' => $this->guest->id])" wire:navigate icon="plus" class="me-auto" data-test="new-reservation-for-guest">
                            {{ __('guests.new_reservation') }}
                        </flux:button>
                    @endif
                    <flux:button variant="primary" type="submit" data-test="save-guest-button">
                        {{ $creating ? __('guests.create') : __('common.save_changes') }}
                    </flux:button>
                </div>
            </form>

            @if ($this->guest)
                <section class="flex flex-col gap-2">
                    <x-section-title>{{ __('guests.history') }}</x-section-title>

                    @forelse ($this->history as $reservation)
                        @php($cancelled = $reservation->trashed())
                        {{-- Opens the reservation panel on top: closing it comes back here. --}}
                        <button
                            type="button"
                            wire:click="$dispatch('open-reservation', { id: {{ $reservation->id }} })"
                            wire:key="history-{{ $reservation->id }}"
                            @class([
                                'flex w-full items-center gap-3 rounded-ui border border-divider px-3 py-2 text-left hover:border-border-control',
                                'bg-surface-alt' => $cancelled,
                                'bg-surface' => ! $cancelled,
                            ])
                        >
                            <x-employee-swatch :color="$reservation->employee->color" />
                            <div @class(['min-w-0 flex-1 text-sm', 'line-through text-subtle' => $cancelled])>
                                <p class="font-semibold text-ink">
                                    {{ __('guests.history_apartment', ['number' => $reservation->apartment->number, 'people' => Format::people($reservation->people_count)]) }}
                                </p>
                                <p class="text-[13px] text-subtle">
                                    {{ Format::date($reservation->check_in_date) }} al {{ Format::date($reservation->check_out_date, true) }}
                                    · {{ trim($reservation->payment_status->label().' '.($reservation->payment_status->hasPayment() ? Format::money($reservation->amount) : '')) }}
                                </p>
                            </div>
                            <x-reservation-timing :timing="$reservation->timing()" />
                        </button>
                    @empty
                        <p class="text-sm text-subtle">{{ __('guests.no_history') }}</p>
                    @endforelse
                </section>
            @endif
        </div>
    </flux:modal>
</div>
