@php
    use App\Support\Format;

    $stretch = "after:absolute after:inset-0 after:content-[''] focus-visible:outline-none focus-visible:after:outline-2 focus-visible:after:-outline-offset-2 focus-visible:after:outline-accent";
    $total = $this->payments->sum(fn ($payment) => (float) $payment->amount);
@endphp

<div class="flex flex-col gap-4">
    <x-page-header :title="__('billing.title')" :description="__('billing.description')" />

    <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
        <flux:checkbox wire:model.live="includeCash" :label="__('billing.include_cash')" data-test="include-cash" />
        <p class="text-[13px] text-subtle" role="status">
            {{ trans_choice('billing.summary', $this->payments->count(), ['count' => $this->payments->count(), 'total' => Format::money($total)]) }}
        </p>
    </div>

    <div class="overflow-x-auto rounded-ui border border-border bg-surface" wire:loading.class="opacity-60" wire:target="includeCash">
        <table class="stack-table w-full min-w-[920px] text-sm">
            <thead class="bg-surface-alt text-left text-xs font-semibold text-muted">
                <tr class="border-b border-divider">
                    <th scope="col" class="px-4 py-2.5">{{ __('billing.columns.guest') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('billing.columns.document') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('billing.columns.stay') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('billing.columns.payment') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('billing.columns.method') }}</th>
                    <th scope="col" class="px-4 py-2.5 text-right">{{ __('billing.columns.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->payments as $payment)
                    @php($reservation = $payment->reservation)
                    @php($cancelled = $reservation->trashed())
                    <tr wire:key="pending-{{ $payment->id }}" class="relative cursor-pointer border-b border-divider-soft last:border-0 hover:bg-accent-softer">
                        <td class="px-4 py-2">
                            <button
                                type="button"
                                wire:click="$dispatch('open-invoice', { id: {{ $payment->id }} })"
                                class="font-semibold text-ink {{ $stretch }}"
                                aria-label="{{ __('billing.open_label', ['guest' => $reservation->guest->full_name]) }}"
                            >{{ $reservation->guest->full_name }}</button>
                        </td>
                        <td data-label="{{ __('billing.columns.document') }}" class="px-4 py-2 {{ $reservation->guest->document_number ? 'text-ink-2' : 'text-faint' }}">
                            {{ $reservation->guest->document_number ? Format::document($reservation->guest->document_number) : __('guests.no_document') }}
                        </td>
                        <td data-label="{{ __('billing.columns.stay') }}" class="px-4 py-2 text-ink-2">
                            {{ __('billing.stay', ['number' => $reservation->apartment->number, 'from' => Format::date($reservation->check_in_date), 'to' => Format::date($reservation->check_out_date, true)]) }}
                        </td>
                        <td data-label="{{ __('billing.columns.payment') }}" class="px-4 py-2">
                            <span class="flex flex-wrap gap-1">
                                <x-payment-badge :status="$reservation->payment_status" />
                                @if ($cancelled)
                                    <x-reservation-timing timing="cancelled" />
                                @endif
                            </span>
                        </td>
                        <td data-label="{{ __('billing.columns.method') }}" class="px-4 py-2 text-ink-2">
                            @if ($payment->method)
                                {{ $payment->method->label() }}
                            @else
                                <span class="inline-flex rounded-[3px] bg-warning-bg px-2 py-0.5 text-xs font-bold text-warning-badge">{{ __('billing.no_method') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('billing.columns.amount') }}" class="px-4 py-2 text-right font-semibold text-ink">{{ Format::money($payment->amount) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-subtle">{{ __('billing.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
