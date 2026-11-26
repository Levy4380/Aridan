{{-- "Agregar pago" inside the reservation detail. Requires the AddsPayment trait. --}}
@php
    use App\Domain\PaymentMethod;
    use App\Domain\PaymentStatus;
    use App\Support\Format;

    $current = $this->reservation->payment_status;
    $outcome = $this->newPaymentOutcome;
    $confirming = $this->isConfirmingPaymentWarnings();
@endphp

<form wire:submit="savePayment" class="flex flex-col gap-3 rounded-ui border border-divider p-3 sm:p-4" data-test="add-payment-form">
    <x-section-title>{{ __('reservations.panel.new_payment') }}</x-section-title>

    @if ($current === PaymentStatus::Pending)
        {{-- A Reservado reservation: the employee chooses what it becomes. --}}
        <flux:radio.group wire:model.live="newPaymentStatus" variant="segmented" :label="__('reservations.panel.becomes')">
            <flux:radio :value="PaymentStatus::Deposit->value" :label="PaymentStatus::Deposit->label()" />
            <flux:radio :value="PaymentStatus::Paid->value" :label="PaymentStatus::Paid->label()" />
        </flux:radio.group>
    @endif

    @if ($this->asksForTotal())
        <flux:input
            wire:model.live.debounce.500ms="newPaymentTotal"
            :label="__('reservations.fields.total_amount')"
            :description="__('reservations.fields.total_amount_help')"
            inputmode="decimal"
            placeholder="$ 0"
            data-test="new-payment-total"
        />
    @endif

    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
        <flux:input wire:model.live.debounce.500ms="newPayment.amount" :label="__('reservations.fields.amount')" inputmode="decimal" placeholder="$ 0" autofocus />
        <flux:radio.group wire:model.live="newPayment.method" variant="segmented" :label="__('reservations.fields.payment_method')">
            @foreach (PaymentMethod::cases() as $method)
                <flux:radio :value="$method->value" :label="$method->label()" />
            @endforeach
        </flux:radio.group>
    </div>
    <flux:error name="newPayment.amount" />
    <flux:checkbox wire:model="newPayment.is_invoiced" :label="__('reservations.fields.invoiced')" />

    @if ($outcome['total'] !== null)
        <p class="text-[13px] text-ink-2" role="status">
            {{ __('reservations.create.balance_summary', ['paid' => Format::money($outcome['paid']), 'total' => Format::money($outcome['total'])]) }}
            @if ($outcome['total'] > $outcome['paid'])
                · <strong class="font-semibold text-ink">{{ __('reservations.create.balance', ['amount' => Format::money($outcome['total'] - $outcome['paid'])]) }}</strong>
            @endif
        </p>
    @endif

    {{-- It reaches the total: offered, already ticked. The employee decides. --}}
    @if ($current === PaymentStatus::Deposit && $outcome['completes'])
        <flux:checkbox wire:model.live="markAsPaid" :label="__('reservations.panel.mark_paid')" :description="__('reservations.panel.mark_paid_help', ['total' => Format::money($outcome['total'])])" data-test="mark-as-paid" />
    @endif

    @error('reservation')
        <flux:callout variant="danger" icon="x-circle" role="alert">
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @enderror

    @if ($confirming)
        <flux:callout variant="warning" icon="exclamation-triangle" role="alert" data-test="payment-warnings">
            <flux:callout.heading>{{ __('reservations.create.warnings_heading') }}</flux:callout.heading>
            <flux:callout.text>
                <ul class="list-disc ps-4">
                    @foreach ($this->newPaymentWarnings as $warning)
                        <li>{{ $warning->message }}</li>
                    @endforeach
                </ul>
            </flux:callout.text>
        </flux:callout>
    @endif

    <div class="flex flex-wrap justify-end gap-2">
        <flux:button variant="filled" wire:click="cancelAddPayment">{{ __('common.cancel') }}</flux:button>
        <flux:button variant="primary" type="submit" data-test="save-payment-button">
            {{ $confirming ? __('reservations.create.save_anyway') : __('reservations.panel.save_payment') }}
        </flux:button>
    </div>
</form>
