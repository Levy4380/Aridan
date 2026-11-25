{{-- Payment, conditions, responsible and note. Requires the EditsReservationDetails trait. --}}
@php
    use App\Domain\PaymentMethod;
    use App\Domain\PaymentStatus;
    use App\Support\Format;
@endphp

{{-- Everything about the money, together: status, total (señada), each payment with its method and "Facturado", and what's left. --}}
<section class="flex flex-col gap-3 rounded-ui border border-divider p-3 sm:p-4" data-test="payment-section">
    <x-section-title>{{ __('reservations.create.payment') }}</x-section-title>
    <flux:radio.group wire:model.live="payment_status" variant="segmented" :aria-label="__('reservations.create.payment_status')">
        @foreach (PaymentStatus::cases() as $status)
            <flux:radio :value="$status->value" :label="$status->label()" />
        @endforeach
    </flux:radio.group>

    @php($currentStatus = PaymentStatus::from($payment_status))

    @if ($currentStatus->hasPayment())
        @if ($currentStatus->hasTotal())
            <flux:input
                wire:model.live.debounce.500ms="total_amount"
                :label="__('reservations.fields.total_amount')"
                :description="__('reservations.fields.total_amount_help')"
                inputmode="decimal"
                placeholder="$ 0"
                data-test="total-amount"
            />
        @endif

        <div class="flex flex-col gap-2">
            @foreach ($payments as $index => $row)
                <div wire:key="payment-{{ $row['key'] }}" class="flex flex-col gap-3 rounded-ui bg-canvas p-3" data-test="payment-row">
                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                        <flux:input
                            wire:model.live.debounce.500ms="payments.{{ $index }}.amount"
                            :label="count($payments) > 1 ? __('reservations.create.payment_n', ['n' => $loop->iteration]) : __('reservations.fields.amount')"
                            inputmode="decimal"
                            placeholder="$ 0"
                        />
                        <flux:radio.group wire:model.live="payments.{{ $index }}.method" variant="segmented" :label="__('reservations.fields.payment_method')">
                            @foreach (PaymentMethod::cases() as $method)
                                <flux:radio :value="$method->value" :label="$method->label()" />
                            @endforeach
                        </flux:radio.group>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        {{-- Always editable, whatever the status. --}}
                        <flux:checkbox wire:model="payments.{{ $index }}.is_invoiced" :label="__('reservations.fields.invoiced')" data-test="invoiced-checkbox" />
                        @if (count($payments) > 1)
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removePayment('{{ $row['key'] }}')" data-test="remove-payment">
                                {{ __('reservations.create.remove_payment') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @php($summary = $this->paymentSummary)
        <div class="flex flex-wrap items-center justify-between gap-2">
            <flux:button size="sm" icon="plus" wire:click="addPayment" data-test="add-payment">{{ __('reservations.create.add_payment') }}</flux:button>
            @if ($summary['paid'] > 0 || $summary['total'] !== null)
                <p class="text-[13px] text-ink-2" role="status" data-test="payment-summary">
                    @if ($summary['total'] === null)
                        {{ __('reservations.create.paid_summary', ['paid' => Format::money($summary['paid'])]) }}
                    @elseif ($summary['balance'] > 0)
                        {{ __('reservations.create.balance_summary', ['paid' => Format::money($summary['paid']), 'total' => Format::money($summary['total'])]) }}
                        · <strong class="font-semibold text-ink">{{ __('reservations.create.balance', ['amount' => Format::money($summary['balance'])]) }}</strong>
                    @else
                        {{ __('reservations.create.balance_summary', ['paid' => Format::money($summary['paid']), 'total' => Format::money($summary['total'])]) }}
                    @endif
                </p>
            @endif
        </div>
    @else
        <p class="text-[13px] text-subtle">{{ __('reservations.create.no_payment_help') }}</p>
    @endif
</section>

<section class="flex flex-col gap-3">
    <x-section-title>{{ __('reservations.create.conditions') }}</x-section-title>

    <flux:checkbox wire:model="is_movable" :label="__('reservations.fields.movable')" :description="__('reservations.fields.movable_help')" />

    <div class="flex flex-wrap items-end gap-3">
        @foreach ($this->extras as $extra)
            @php($pressed = in_array($extra->id, $extra_ids, true))
            <button
                type="button"
                wire:key="extra-{{ $extra->id }}"
                wire:click="toggleExtra({{ $extra->id }})"
                aria-pressed="{{ $pressed ? 'true' : 'false' }}"
                @class([
                    'flex h-11 items-center gap-2 rounded-ui px-3 text-sm font-semibold',
                    'border-2 border-accent bg-accent-softer text-accent' => $pressed,
                    'border border-border-control bg-surface text-ink-2 hover:bg-canvas' => ! $pressed,
                ])
            >
                @if ($extra->icon())
                    <x-dynamic-component :component="'icon.'.$extra->icon()" class="size-4" />
                @endif
                {{ $extra->name }}
            </button>
        @endforeach

        <div class="flex flex-col gap-[5px]">
            <span class="text-sm font-medium text-ink" id="mattresses-label-{{ $this->getId() }}">{{ __('reservations.fields.extra_mattresses') }}</span>
            <div class="flex items-center" role="group" aria-labelledby="mattresses-label-{{ $this->getId() }}">
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

{{-- Who and what else: the responsible employee and the free-text note. --}}
<section class="flex flex-col gap-3">
    <x-section-title>{{ __('reservations.create.responsible_and_notes') }}</x-section-title>
    <div class="flex flex-col gap-2">
        <flux:select wire:model="employee_id" :label="__('reservations.create.responsible')">
            @foreach ($this->employees as $employee)
                <flux:select.option :value="$employee->id">
                    {{ $employee->name }}@if ($employee->is(auth()->user())) ({{ __('employees.you') }})@endif@unless ($employee->is_active) · {{ __('common.inactive') }}@endunless
                </flux:select.option>
            @endforeach
        </flux:select>
        <p class="text-[13px] text-subtle">{{ __('reservations.create.responsible_help') }}</p>
        <flux:error name="employee_id" />
    </div>

    <flux:textarea wire:model="notes" :label="__('reservations.fields.notes')" :placeholder="__('reservations.fields.notes_placeholder')" rows="2" />
</section>

@error('reservation')
    <flux:callout variant="danger" icon="x-circle" role="alert" data-test="reservation-error">
        <flux:callout.text>{{ $message }}</flux:callout.text>
    </flux:callout>
@enderror

@if ($this->isConfirmingWarnings())
    <flux:callout variant="warning" icon="exclamation-triangle" role="alert" data-test="pending-warnings">
        <flux:callout.heading>{{ __('reservations.create.warnings_heading') }}</flux:callout.heading>
        <flux:callout.text>
            <ul class="list-disc ps-4">
                @foreach ($this->warnings as $warning)
                    <li>{{ $warning->message }}</li>
                @endforeach
            </ul>
        </flux:callout.text>
    </flux:callout>
@endif
