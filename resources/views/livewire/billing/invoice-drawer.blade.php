@php
    use App\Support\Format;

    $reservation = $this->reservation;
@endphp

<div>
    <flux:modal variant="flyout" wire:model.self.live="show" class="w-full max-md:max-w-none max-md:p-5 md:w-[480px]" data-test="invoice-drawer">
        @if ($reservation)
            <div class="flex flex-col gap-5">
                <div class="pe-8">
                    <x-section-title>{{ __('billing.drawer.title') }}</x-section-title>
                    <h2 class="mt-1 text-[22px] font-bold leading-tight text-ink">{{ $reservation->guest->full_name }}</h2>
                    <p class="text-sm text-ink-2">
                        {{ __('billing.stay', ['number' => $reservation->apartment->number, 'from' => Format::date($reservation->check_in_date), 'to' => Format::date($reservation->check_out_date, true)]) }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <x-stat-tile :label="__('billing.drawer.amount')" class="col-span-2">
                        <span class="text-[26px]" data-test="invoice-amount">{{ $reservation->amount !== null ? Format::money($reservation->amount) : '—' }}</span>
                        <span class="mt-1 flex flex-wrap gap-1">
                            <x-payment-badge :status="$reservation->payment_status" />
                            @if ($reservation->payment_method)
                                <span class="inline-flex rounded-[3px] bg-chip px-2 py-0.5 text-xs font-bold text-subtle">{{ $reservation->payment_method->label() }}</span>
                            @else
                                <span class="inline-flex rounded-[3px] bg-warning-bg px-2 py-0.5 text-xs font-bold text-warning-badge">{{ __('billing.no_method') }}</span>
                            @endif
                            @if ($reservation->trashed())
                                <x-reservation-timing timing="cancelled" />
                            @endif
                        </span>
                    </x-stat-tile>
                    <x-stat-tile :label="__('billing.drawer.document')">
                        @if ($reservation->guest->document_number)
                            <span data-test="invoice-document">{{ Format::document($reservation->guest->document_number) }}</span>
                        @else
                            <span class="text-faint">{{ __('guests.no_document') }}</span>
                        @endif
                    </x-stat-tile>
                    <x-stat-tile :label="__('billing.drawer.guest')">{{ $reservation->guest->full_name }}</x-stat-tile>
                </div>

                @unless ($reservation->guest->document_number)
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        <flux:callout.text>{{ __('billing.drawer.no_document_help') }}</flux:callout.text>
                        <x-slot name="actions">
                            <flux:button size="sm" wire:click="$dispatch('open-guest', { id: {{ $reservation->guest_id }} })">{{ __('guests.view') }}</flux:button>
                        </x-slot>
                    </flux:callout>
                @endunless

                @if ($reservation->isInvoiced())
                    <flux:callout variant="success" icon="check-circle">
                        <flux:callout.text>{{ __('reservations.invoicing.by', ['name' => $reservation->invoicedBy?->name ?? '—', 'date' => Format::date($reservation->invoiced_at, true)]) }}</flux:callout.text>
                    </flux:callout>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-divider pt-4">
                    <flux:button variant="ghost" wire:click="$dispatch('open-reservation', { id: {{ $reservation->id }} })">{{ __('billing.drawer.view_reservation') }}</flux:button>
                    @unless ($reservation->isInvoiced())
                        <flux:button variant="primary" icon="check" wire:click="markInvoiced" data-test="mark-invoiced">{{ __('billing.drawer.mark') }}</flux:button>
                    @endunless
                </div>
            </div>
        @endif
    </flux:modal>
</div>
