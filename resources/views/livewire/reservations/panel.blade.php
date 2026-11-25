@php
    use App\Livewire\Reservations\Panel;
    use App\Support\Format;

    $reservation = $this->reservation;
@endphp

<div>
    <flux:modal variant="flyout" wire:model.self.live="show" class="w-full max-md:max-w-none max-md:p-5 md:w-[560px]" data-test="reservation-panel">
        @if ($reservation)
            @php
                $stay = $reservation->stay();
                $cancelled = $reservation->trashed();
                $half = fn ($date, bool $morning) => $date->locale('es')->isoFormat('ddd').' '.Format::date($date)
                    .' ('.__($morning ? 'reservations.half.morning' : 'reservations.half.afternoon').')';
                $stayText = __('reservations.panel.stay_line', [
                    'number' => $reservation->apartment->number,
                    'from' => $half($reservation->check_in_date, $reservation->early_check_in),
                    'to' => $half($reservation->check_out_date, ! $reservation->late_check_out),
                    'nights' => trans_choice('availability.nights', $stay->nights(), ['count' => $stay->nights()]),
                ]);
            @endphp

            <div class="flex flex-col gap-5">
                {{-- Header --}}
                <div class="flex flex-col gap-1 pe-8">
                    <p class="flex items-center gap-2 text-[13px] text-subtle">
                        <x-employee-swatch :color="$reservation->employee->color" />
                        {{ __('reservations.panel.responsible', ['name' => $reservation->employee->name, 'date' => Format::date($reservation->created_at, true)]) }}
                    </p>
                    <h2 @class(['text-[22px] font-bold leading-tight text-ink', 'line-through' => $cancelled])>{{ $reservation->guest->full_name }}</h2>
                    <p class="text-sm text-ink-2">{{ $stayText }}</p>
                </div>

                @if ($cancelled)
                    <flux:callout variant="danger" icon="x-circle" role="status" data-test="cancelled-banner">
                        <flux:callout.heading>{{ __('reservations.panel.cancelled_heading') }}</flux:callout.heading>
                        <flux:callout.text>
                            {{ __('reservations.panel.cancelled_by', [
                                'name' => $reservation->cancelledBy?->name ?? '—',
                                'date' => Format::date($reservation->deleted_at, true),
                                'number' => $reservation->apartment->number,
                            ]) }}
                        </flux:callout.text>
                    </flux:callout>
                @endif

                @if ($notice)
                    <flux:callout variant="success" icon="check-circle" role="status" data-test="panel-notice">
                        <flux:callout.text>{{ $notice }}</flux:callout.text>
                    </flux:callout>
                @endif

                @switch($mode)
                    @case(Panel::EDIT)
                        <form wire:submit="save" class="flex flex-col gap-5">
                            <x-stay-summary :stay="$stay" :people="$reservation->people_count">
                                <flux:button size="sm" :href="route('availability', ['editar' => $reservation->id])" wire:navigate data-test="change-dates-button">{{ __('reservations.create.change') }}</flux:button>
                            </x-stay-summary>
                            <p class="-mt-3 text-[13px] text-subtle">{{ __('reservations.panel.change_help') }}</p>

                            <section class="flex flex-col gap-2">
                                <x-section-title>{{ __('reservations.create.guest') }}</x-section-title>
                                @include('livewire.partials.guest-picker')
                            </section>

                            @include('livewire.partials.reservation-details')

                            <div class="flex justify-end gap-2">
                                <flux:button variant="filled" wire:click="back">{{ __('common.cancel') }}</flux:button>
                                <flux:button variant="primary" type="submit" data-test="save-changes-button">
                                    {{ $this->isConfirmingWarnings() ? __('reservations.create.save_anyway') : __('common.save_changes') }}
                                </flux:button>
                            </div>
                        </form>
                        @break

                    @case(Panel::MOVE)
                        <div class="flex flex-col gap-4">
                            @unless ($reservation->is_movable)
                                <flux:callout variant="warning" icon="lock-closed">
                                    <flux:callout.text>{{ __('reservations.panel.not_movable_help') }}</flux:callout.text>
                                </flux:callout>
                                <flux:checkbox wire:model.live="moveAnyway" :label="__('reservations.panel.move_anyway')" />
                                <flux:error name="moveAnyway" />
                            @endunless

                            <x-section-title>
                                {{ __('reservations.panel.free_for', [
                                    'from' => Format::date($reservation->check_in_date),
                                    'to' => Format::date($reservation->check_out_date),
                                    'people' => trans_choice('reservations.people_long', $reservation->people_count, ['count' => $reservation->people_count]),
                                ]) }}
                            </x-section-title>

                            @if ($this->moveOptions === [])
                                <p class="rounded-ui border border-border p-4 text-center text-sm text-subtle">{{ __('reservations.panel.no_free') }}</p>
                            @else
                                <div class="flex max-h-80 flex-col gap-2 overflow-y-auto" role="radiogroup" aria-label="{{ __('reservations.panel.move_title') }}">
                                    @foreach ($this->moveOptions as $option)
                                        @php($apartment = $option->availability->apartment)
                                        <button
                                            type="button"
                                            role="radio"
                                            aria-checked="{{ $moveTo === $apartment->id ? 'true' : 'false' }}"
                                            wire:click="$set('moveTo', {{ $apartment->id }})"
                                            wire:key="move-{{ $apartment->id }}"
                                            @class([
                                                'flex flex-wrap items-center gap-x-3 gap-y-1 rounded-ui p-2.5 text-left',
                                                'border-2 border-accent bg-accent-softer' => $moveTo === $apartment->id,
                                                'border border-border-control bg-surface hover:bg-canvas' => $moveTo !== $apartment->id,
                                            ])
                                        >
                                            <span class="text-[17px] font-bold text-ink">{{ $apartment->number }}</span>
                                            <span class="text-xs text-subtle">{{ Format::people($apartment->capacity) }}</span>
                                            <x-fit-badge :fit="$option->fit" />
                                            <x-availability.gap :availability="$option->availability" class="text-xs text-subtle" />
                                            @if ($option->isBest())
                                                <span class="text-xs font-bold text-accent">{{ __('availability.best') }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            <flux:error name="moveTo" />
                            @error('reservation')
                                <flux:callout variant="danger" icon="x-circle" role="alert">
                                    <flux:callout.text>{{ $message }}</flux:callout.text>
                                </flux:callout>
                            @enderror

                            <div class="flex justify-end gap-2">
                                <flux:button variant="filled" wire:click="back">{{ __('reservations.panel.back') }}</flux:button>
                                <flux:button
                                    variant="primary"
                                    wire:click="move"
                                    :disabled="! $moveTo || (! $reservation->is_movable && ! $moveAnyway)"
                                    data-test="confirm-move-button"
                                >
                                    {{ $moveTo ? __('reservations.panel.move_to', ['number' => $this->moveOptions ? collect($this->moveOptions)->first(fn ($o) => $o->availability->apartment->id === $moveTo)?->availability->apartment->number : '']) : __('reservations.panel.move_title') }}
                                </flux:button>
                            </div>
                        </div>
                        @break

                    @case(Panel::CANCEL)
                        <div role="alertdialog" aria-labelledby="cancel-title" aria-describedby="cancel-text" class="flex flex-col gap-3 rounded-ui border border-danger-border bg-danger-bg p-4">
                            <h3 id="cancel-title" class="text-[17px] font-bold text-danger-fg">{{ __('reservations.panel.cancel_question') }}</h3>
                            <p id="cancel-text" class="text-sm text-danger-fg">
                                {{ __('reservations.panel.cancel_help', [
                                    'number' => $reservation->apartment->number,
                                    'from' => Format::date($reservation->check_in_date),
                                    'to' => Format::date($reservation->check_out_date),
                                ]) }}
                            </p>
                            <div class="flex justify-end gap-2">
                                <flux:button variant="filled" wire:click="back">{{ __('reservations.panel.no_back') }}</flux:button>
                                <flux:button variant="danger" wire:click="cancel" data-test="confirm-cancel-button">{{ __('reservations.panel.yes_cancel') }}</flux:button>
                            </div>
                        </div>
                        @break

                    @default
                        {{-- Detail tiles --}}
                        <div class="grid grid-cols-2 gap-2">
                            <x-stat-tile :label="__('reservations.panel.people')">
                                {{ $reservation->people_count }}
                                @if ($reservation->extra_mattresses)
                                    + {{ trans_choice('reservations.panel.mattresses', $reservation->extra_mattresses, ['count' => $reservation->extra_mattresses]) }}
                                @endif
                                <span class="text-xs font-medium text-subtle">· {{ __('reservations.panel.capacity', ['count' => $reservation->apartment->capacity]) }}</span>
                            </x-stat-tile>
                            <x-stat-tile :label="__('reservations.panel.payment')" data-test="payment-tile">
                                {{ $reservation->payment_status->label() }}
                                @if ($reservation->payment_status->hasPayment() && $reservation->paidAmount() !== null)
                                    · {{ $reservation->total_amount !== null && $reservation->payment_status->hasTotal()
                                        ? __('reservations.panel.paid_of', ['paid' => Format::money($reservation->paidAmount()), 'total' => Format::money($reservation->total_amount)])
                                        : Format::money($reservation->paidAmount()) }}
                                @endif
                                @if (($balance = $reservation->balance()) !== null && $balance > 0)
                                    <span class="block text-xs font-medium text-subtle">{{ __('reservations.create.balance', ['amount' => Format::money($balance)]) }}</span>
                                @endif
                            </x-stat-tile>
                            <x-stat-tile :label="__('reservations.panel.condition')">
                                <span class="flex items-center gap-1.5">
                                    @unless ($reservation->is_movable)
                                        <flux:icon.lock-closed variant="micro" class="size-4" />
                                    @endunless
                                    {{ $reservation->is_movable ? __('reservations.fields.movable') : __('calendar.fixed') }}
                                </span>
                            </x-stat-tile>
                            <x-stat-tile :label="__('reservations.panel.extras')">
                                {{ $reservation->extras->isEmpty() ? __('reservations.panel.no_extras') : $reservation->extras->pluck('name')->join(', ') }}
                            </x-stat-tile>
                        </div>

                        @if ($reservation->early_check_in || $reservation->late_check_out)
                            <p class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-2">
                                @if ($reservation->early_check_in)
                                    <span class="flex items-center gap-1.5"><x-icon.sunrise class="size-4" /> {{ __('availability.early_check_in') }}</span>
                                @endif
                                @if ($reservation->late_check_out)
                                    <span class="flex items-center gap-1.5"><flux:icon.moon variant="micro" class="size-4" /> {{ __('availability.late_check_out') }}</span>
                                @endif
                            </p>
                        @endif

                        {{-- Each payment with its "Facturado": always editable, also on a cancelled reservation. --}}
                        @if ($reservation->payments->isNotEmpty())
                            <section class="flex flex-col gap-2">
                                <x-section-title>{{ __('reservations.panel.payments') }}</x-section-title>
                                <ul class="flex flex-col divide-y divide-divider rounded-ui border border-divider">
                                    @foreach ($reservation->payments as $payment)
                                        <li wire:key="paid-{{ $payment->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 px-3 py-2" data-test="invoicing-row">
                                            <span class="font-bold text-ink">{{ Format::money($payment->amount) }}</span>
                                            @if ($payment->method)
                                                <span class="text-[13px] text-ink-2">{{ $payment->method->label() }}</span>
                                            @else
                                                <span class="inline-flex rounded-[3px] bg-warning-bg px-2 py-0.5 text-xs font-bold text-warning-badge">{{ __('reservations.panel.no_method') }}</span>
                                            @endif
                                            <x-invoiced-badge :invoiced="$payment->isInvoiced()" />
                                            <span class="min-w-0 flex-1 basis-40 text-[13px] text-subtle">
                                                @if ($payment->isInvoiced())
                                                    {{ __('reservations.invoicing.by', ['name' => $payment->invoicedBy?->name ?? '—', 'date' => Format::date($payment->invoiced_at, true)]) }}
                                                @else
                                                    {{ __('reservations.invoicing.pending_help') }}
                                                @endif
                                            </span>
                                            <flux:button size="sm" wire:click="toggleInvoiced({{ $payment->id }})" class="max-sm:w-full" data-test="toggle-invoiced">
                                                {{ $payment->isInvoiced() ? __('reservations.invoicing.unmark') : __('reservations.invoicing.mark') }}
                                            </flux:button>
                                        </li>
                                    @endforeach
                                </ul>
                            </section>
                        @endif

                        <section class="flex flex-col gap-2">
                            <x-section-title>{{ __('reservations.create.guest') }}</x-section-title>
                            <x-guest-card :guest="$reservation->guest">
                                <flux:button size="sm" variant="ghost" wire:click="$dispatch('open-guest', { id: {{ $reservation->guest_id }} })" data-test="view-guest">{{ __('guests.view') }}</flux:button>
                            </x-guest-card>
                        </section>

                        <section class="flex flex-col gap-2">
                            <x-section-title>{{ __('reservations.fields.notes') }}</x-section-title>
                            <p class="whitespace-pre-line text-sm text-ink-2">{{ $reservation->notes ?: __('reservations.panel.no_notes') }}</p>
                        </section>

                        @unless ($cancelled)
                            <div class="flex flex-wrap gap-2 border-t border-divider pt-4">
                                <flux:button variant="primary" icon="pencil-square" wire:click="startEdit" class="max-sm:w-full" data-test="edit-button">{{ __('reservations.panel.edit') }}</flux:button>
                                <flux:button icon="arrows-right-left" wire:click="startMove" class="max-sm:w-full" data-test="move-button">{{ __('reservations.panel.move') }}</flux:button>
                                <flux:button variant="ghost" class="border border-danger-border text-danger! max-sm:w-full sm:ms-auto" wire:click="startCancel" data-test="cancel-button">{{ __('reservations.panel.cancel') }}</flux:button>
                            </div>
                        @endunless
                @endswitch
            </div>
        @endif
    </flux:modal>
</div>
