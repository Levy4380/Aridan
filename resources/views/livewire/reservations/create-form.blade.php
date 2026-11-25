@php
    use App\Support\Format;

    $confirming = $this->isConfirmingWarnings();
@endphp

<div>
    <flux:modal variant="flyout" wire:model.self="show" class="w-full max-md:max-w-none max-md:p-5 md:w-[560px]" data-test="reservation-drawer">
        @if ($stay)
            <form wire:submit="save" class="flex flex-col gap-5">
                <h2 class="text-[20px] font-bold text-ink">{{ __('reservations.create.title') }}</h2>

                {{-- Summary of what the search fixed --}}
                <x-stay-summary :stay="$stay" :people="$people_count">
                    <flux:button size="sm" wire:click="$set('show', false)">{{ __('reservations.create.change') }}</flux:button>
                </x-stay-summary>

                {{-- Apartment --}}
                <section class="flex flex-col gap-2">
                    <x-section-title>{{ __('reservations.create.apartment') }}</x-section-title>
                    <div class="grid gap-2 sm:grid-cols-3" role="radiogroup" aria-label="{{ __('reservations.create.apartment') }}">
                        @foreach ($this->options as $option)
                            @php($apartment = $option->availability->apartment)
                            <button
                                type="button"
                                role="radio"
                                aria-checked="{{ $apartment->id === $apartment_id ? 'true' : 'false' }}"
                                wire:click="chooseApartment({{ $apartment->id }})"
                                wire:key="option-{{ $apartment->id }}"
                                @class([
                                    'flex flex-col items-start gap-1 rounded-ui p-2.5 text-left',
                                    'border-2 border-accent bg-accent-softer' => $apartment->id === $apartment_id,
                                    'border border-border-control bg-surface hover:bg-canvas' => $apartment->id !== $apartment_id,
                                ])
                            >
                                <span class="flex items-baseline gap-1.5">
                                    <span class="text-[17px] font-bold text-ink">{{ $apartment->number }}</span>
                                    <span class="text-xs text-subtle">{{ Format::people($apartment->capacity) }}</span>
                                </span>
                                <x-fit-badge :fit="$option->fit" />
                                <x-availability.gap :availability="$option->availability" class="text-xs text-subtle" />
                                @if ($option->isBest())
                                    <span class="text-xs font-bold text-accent">{{ __('availability.best') }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </section>

                {{-- Guest --}}
                <section class="flex flex-col gap-2">
                    <x-section-title>{{ __('reservations.create.guest') }} <span class="text-danger">*</span></x-section-title>
                    @include('livewire.partials.guest-picker')
                </section>

                @include('livewire.partials.reservation-details')

                <div class="-mx-5 -mb-5 px-5 md:-mx-6 md:-mb-6 md:px-6 flex flex-wrap items-center justify-end gap-2 border-t border-divider bg-surface-alt py-4">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('common.cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit" data-test="save-reservation-button">
                        <span wire:loading.remove wire:target="save">{{ $confirming ? __('reservations.create.save_anyway') : __('reservations.create.save') }}</span>
                        <span wire:loading wire:target="save">{{ __('reservations.create.saving') }}</span>
                    </flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
