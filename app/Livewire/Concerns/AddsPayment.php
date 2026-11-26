<?php

namespace App\Livewire\Concerns;

use App\Actions\Reservations\AddPayment;
use App\Domain\PaymentLine;
use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Domain\ReservationWarnings;
use App\Domain\Warning;
use App\Models\Payment;
use App\Models\Reservation;
use App\Support\Format;
use Livewire\Attributes\Computed;

/**
 * "Agregar pago" in the reservation detail: one payment without going
 * through the whole edit. View: livewire/partials/add-payment.
 *
 * The using component provides the open `reservation` and `afterChange()`.
 */
trait AddsPayment
{
    public bool $addingPayment = false;

    /** @var array{amount: string, method: string|null, is_invoiced: bool} */
    public array $newPayment = ['amount' => '', 'method' => null, 'is_invoiced' => false];

    /** Only for a Reservado reservation: what it becomes with this payment. */
    public string $newPaymentStatus = 'deposit';

    /** Only when it stays (or becomes) señada without a total yet. */
    public string $newPaymentTotal = '';

    /** Offered, already ticked, when this payment reaches the total. */
    public bool $markAsPaid = true;

    /** Signature of the warnings already accepted with "Guardar igual". */
    public ?string $acceptedPaymentWarnings = null;

    public function startAddPayment(): void
    {
        if (! $this->reservation || $this->reservation->trashed()) {
            return;
        }

        $this->resetValidation();
        $this->newPayment = ['amount' => '', 'method' => null, 'is_invoiced' => false];
        $this->newPaymentStatus = PaymentStatus::Deposit->value;
        $this->newPaymentTotal = '';
        $this->markAsPaid = true;
        $this->acceptedPaymentWarnings = null;
        $this->notice = null;
        $this->addingPayment = true;
    }

    public function cancelAddPayment(): void
    {
        $this->addingPayment = false;
        $this->resetValidation();
    }

    /**
     * Whether the form asks for the total: señada (now or after this
     * payment) and without a total loaded yet.
     */
    public function asksForTotal(): bool
    {
        $reservation = $this->reservation;

        return $reservation->payment_status === PaymentStatus::Pending
            ? $this->newPaymentStatus === PaymentStatus::Deposit->value
            : $reservation->payment_status === PaymentStatus::Deposit && $reservation->total_amount === null;
    }

    /**
     * The total and what would be received with this payment.
     *
     * @return array{total: float|null, paid: float, completes: bool}
     */
    #[Computed]
    public function newPaymentOutcome(): array
    {
        $reservation = $this->reservation;
        $total = $this->asksForTotal() ? Format::parseMoney($this->newPaymentTotal) : ($reservation->total_amount !== null ? (float) $reservation->total_amount : null);
        $total = $total !== null && $total > 0 ? $total : null;
        $paid = (float) $reservation->paidAmount() + (Format::parseMoney($this->newPayment['amount']) ?? 0.0);

        $resultsInDeposit = $reservation->payment_status === PaymentStatus::Deposit
            || ($reservation->payment_status === PaymentStatus::Pending && $this->newPaymentStatus === PaymentStatus::Deposit->value);

        return ['total' => $total, 'paid' => $paid, 'completes' => $resultsInDeposit && $total !== null && $paid >= $total];
    }

    /**
     * The status the reservation ends with: chosen by the employee, never
     * derived from the money.
     */
    public function newPaymentResultStatus(): PaymentStatus
    {
        $reservation = $this->reservation;

        return match ($reservation->payment_status) {
            PaymentStatus::Pending => PaymentStatus::from($this->newPaymentStatus),
            PaymentStatus::Deposit => $this->newPaymentOutcome['completes'] && $this->markAsPaid ? PaymentStatus::Paid : PaymentStatus::Deposit,
            PaymentStatus::Paid => PaymentStatus::Paid,
        };
    }

    /**
     * @return list<Warning>
     */
    #[Computed]
    public function newPaymentWarnings(): array
    {
        $reservation = $this->reservation;
        $status = $this->newPaymentResultStatus();
        $lines = [
            ...$reservation->payments->map(fn (Payment $payment) => new PaymentLine((float) $payment->amount, $payment->method))->all(),
            new PaymentLine(Format::parseMoney($this->newPayment['amount']), PaymentMethod::tryFrom((string) $this->newPayment['method'])),
        ];
        $total = $this->newPaymentOutcome['total'];
        $warnings = app(ReservationWarnings::class);

        return array_values(array_filter([
            $warnings->missingAmount($status, $lines),
            $warnings->missingTotal($status, $total),
            $warnings->depositCoversTotal($status, $lines, $total),
        ]));
    }

    public function isConfirmingPaymentWarnings(): bool
    {
        $signature = collect($this->newPaymentWarnings)->pluck('code')->sort()->join(',');

        return $signature !== '' && $signature === $this->acceptedPaymentWarnings;
    }

    public function savePayment(AddPayment $addPayment): void
    {
        $reservation = $this->reservation;

        if (! $reservation) {
            return;
        }

        $amount = Format::parseMoney($this->newPayment['amount']);

        if ($amount === null || $amount <= 0) {
            $this->addError('newPayment.amount', __('reservations.panel.amount_required'));

            return;
        }

        // The system warns, it doesn't block: the second save goes ahead.
        $signature = collect($this->newPaymentWarnings)->pluck('code')->sort()->join(',');

        if ($signature !== '' && $signature !== $this->acceptedPaymentWarnings) {
            $this->acceptedPaymentWarnings = $signature;

            return;
        }

        $status = $this->newPaymentResultStatus();

        $addPayment($reservation, [
            'amount' => $amount,
            'method' => $this->newPayment['method'] ?: null,
            'is_invoiced' => (bool) $this->newPayment['is_invoiced'],
            'payment_status' => $status->value,
            'total_amount' => $this->asksForTotal() ? Format::parseMoney($this->newPaymentTotal) : null,
        ], auth()->user());

        $this->addingPayment = false;
        $this->afterChange(__('reservations.panel.payment_saved', [
            'amount' => Format::money($amount),
            'status' => $status->label(),
        ]));
    }
}
