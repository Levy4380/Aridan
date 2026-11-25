@php
    use App\Domain\PaymentMethod;
    use App\Domain\PaymentStatus;
    use App\Support\Format;

    $signature = collect($this->warnings)->pluck('code')->sort()->join(',');
    $confirming = $signature !== '' && $signature === $acceptedWarnings;
@endphp

<div>
    <flux:modal wire:model.self="show" class="w-full md:w-[580px]" data-test="reservation-modal">
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

                {{-- Payment --}}
                <section class="flex flex-col gap-3">
                    <x-section-title>{{ __('reservations.create.payment') }}</x-section-title>
                    <flux:radio.group wire:model.live="payment_status" variant="segmented">
                        @foreach (PaymentStatus::cases() as $status)
                            <flux:radio :value="$status->value" :label="$status->label()" />
                        @endforeach
                    </flux:radio.group>

                    @if (PaymentStatus::from($payment_status)->hasPayment())
                        <div class="grid gap-3 sm:grid-cols-2">
                            <flux:radio.group wire:model.live="payment_method" variant="segmented" :label="__('reservations.fields.payment_method')">
                                @foreach (PaymentMethod::cases() as $method)
                                    <flux:radio :value="$method->value" :label="$method->label()" />
                                @endforeach
                            </flux:radio.group>
                            <flux:input wire:model.live.debounce.500ms="amount" :label="__('reservations.fields.amount')" inputmode="decimal" placeholder="$ 0" />
                        </div>
                    @else
                        <p class="text-[13px] text-subtle">{{ __('reservations.create.no_payment_help') }}</p>
                    @endif
                </section>

                {{-- Conditions --}}
                <section class="flex flex-col gap-3">
                    <x-section-title>{{ __('reservations.create.conditions') }}</x-section-title>

                    <flux:checkbox wire:model="is_movable" :label="__('reservations.fields.movable')" :description="__('reservations.fields.movable_help')" />

                    <div class="flex flex-wrap items-end gap-3">
                        @foreach ($this->extras as $extra)
                            <button
                                type="button"
                                wire:key="extra-{{ $extra->id }}"
                                wire:click="toggleExtra({{ $extra->id }})"
                                aria-pressed="{{ in_array($extra->id, $extra_ids, true) ? 'true' : 'false' }}"
                                @class([
                                    'flex h-11 items-center gap-2 rounded-ui px-3 text-sm font-semibold',
                                    'border-2 border-accent bg-accent-softer text-accent' => in_array($extra->id, $extra_ids, true),
                                    'border border-border-control bg-surface text-ink-2 hover:bg-canvas' => ! in_array($extra->id, $extra_ids, true),
                                ])
                            >
                                @if ($extra->icon())
                                    <x-dynamic-component :component="'icon.'.$extra->icon()" class="size-4" />
                                @endif
                                {{ $extra->name }}
                            </button>
                        @endforeach

                        <div class="flex flex-col gap-[5px]">
                            <span class="text-sm font-medium text-ink" id="mattresses-label">{{ __('reservations.fields.extra_mattresses') }}</span>
                            <div class="flex items-center" role="group" aria-labelledby="mattresses-label">
                                <flux:button square wire:click="removeMattress" icon="minus" :aria-label="__('reservations.create.mattress_less')" :disabled="$extra_mattresses === 0" />
                                <span class="w-10 text-center text-base font-bold" aria-live="polite">{{ $extra_mattresses }}</span>
                                <flux:button square wire:click="addMattress" icon="plus" :aria-label="__('reservations.create.mattress_more')" :disabled="$extra_mattresses >= 6" />
                            </div>
                        </div>
                    </div>

                    @if ($this->capacityWarning)
                        <flux:callout variant="warning" icon="exclamation-triangle" data-test="capacity-warning">
                            <flux:callout.text>{{ $this->capacityWarning->message }}</flux:callout.text>
                        </flux:callout>
                    @endif
                </section>

                {{-- Responsible employee --}}
                <section class="flex flex-col gap-2">
                    <x-section-title>{{ __('reservations.create.responsible') }}</x-section-title>
                    <flux:select wire:model="employee_id" :aria-label="__('reservations.create.responsible')">
                        @foreach ($this->employees as $employee)
                            <flux:select.option :value="$employee->id">{{ $employee->name }}@if ($employee->is(auth()->user())) ({{ __('employees.you') }})@endif</flux:select.option>
                        @endforeach
                    </flux:select>
                    <p class="text-[13px] text-subtle">{{ __('reservations.create.responsible_help') }}</p>
                    <flux:error name="employee_id" />
                </section>

                {{-- Notes --}}
                <flux:textarea wire:model="notes" :label="__('reservations.fields.notes')" :placeholder="__('reservations.fields.notes_placeholder')" rows="2" />

                @error('reservation')
                    <flux:callout variant="danger" icon="x-circle" role="alert" data-test="reservation-error">
                        <flux:callout.text>{{ $message }}</flux:callout.text>
                    </flux:callout>
                @enderror

                @if ($confirming)
                    <flux:callout variant="warning" icon="exclamation-triangle" role="alert" data-test="pending-warnings">
                        <flux:callout.heading>{{ __('reservations.create.warnings_heading') }}</flux:callout.heading>
                        <flux:callout.text>
                            <ul class="list-disc ps-4">
                                @foreach ($pendingWarnings as $warning)
                                    <li>{{ $warning->message }}</li>
                                @endforeach
                            </ul>
                        </flux:callout.text>
                    </flux:callout>
                @endif

                <div class="-mx-6 -mb-6 flex flex-wrap items-center justify-end gap-2 border-t border-divider bg-surface-alt px-6 py-4">
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
