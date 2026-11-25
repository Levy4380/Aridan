<?php

namespace App\Livewire\Concerns;

use App\Domain\PaymentLine;
use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Domain\ReservationWarnings;
use App\Domain\Warning;
use App\Models\Apartment;
use App\Models\Extra;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Format;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * The reservation fields that don't change the occupancy (payment,
 * conditions, responsible, note), shared by the new-reservation modal and
 * the edit panel. View: livewire/partials/reservation-details.
 *
 * The using component provides $apartment_id and $people_count.
 */
trait EditsReservationDetails
{
    public ?int $employee_id = null;

    public string $payment_status = 'deposit';

    /** Total de la reserva: only when señada. */
    public string $total_amount = '';

    /**
     * The payments as typed: id (null when new), amount, method, is_invoiced,
     * and a key so the rows keep their state when one is removed.
     *
     * @var list<array{key: string, id: int|null, amount: string, method: string|null, is_invoiced: bool}>
     */
    public array $payments = [];

    public bool $is_movable = true;

    /** @var list<int> */
    public array $extra_ids = [];

    public int $extra_mattresses = 0;

    public string $notes = '';

    /** Signature of the warnings the employee already accepted with "Guardar igual". */
    public ?string $acceptedWarnings = null;

    protected function resetDetails(): void
    {
        $this->employee_id = auth()->id();
        $this->payment_status = PaymentStatus::Deposit->value;
        $this->total_amount = '';
        $this->payments = [$this->blankPayment()];
        $this->is_movable = true;
        $this->extra_ids = [];
        $this->extra_mattresses = 0;
        $this->notes = '';
        $this->acceptedWarnings = null;
    }

    protected function fillDetailsFrom(Reservation $reservation): void
    {
        $this->employee_id = $reservation->employee_id;
        $this->payment_status = $reservation->payment_status->value;
        $this->total_amount = $reservation->total_amount !== null ? Format::money($reservation->total_amount) : '';
        $this->payments = $reservation->payments->map(fn (Payment $payment) => [
            'key' => Str::random(8),
            'id' => $payment->id,
            'amount' => Format::money($payment->amount),
            'method' => $payment->method?->value,
            'is_invoiced' => $payment->isInvoiced(),
        ])->all() ?: [$this->blankPayment()];
        $this->is_movable = $reservation->is_movable;
        $this->extra_ids = $reservation->extras->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->extra_mattresses = $reservation->extra_mattresses;
        $this->notes = (string) $reservation->notes;
        $this->acceptedWarnings = null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function detailsPayload(): array
    {
        return [
            'employee_id' => $this->employee_id,
            'payment_status' => $this->payment_status,
            'total_amount' => Format::parseMoney($this->total_amount),
            // A row without amount is left out: it was already warned.
            'payments' => collect($this->payments)
                ->map(fn (array $row) => [
                    'id' => $row['id'] ?? null,
                    'amount' => Format::parseMoney($row['amount']),
                    'method' => ($row['method'] ?? null) ?: null,
                    'is_invoiced' => (bool) ($row['is_invoiced'] ?? false),
                ])
                ->filter(fn (array $row) => $row['amount'] !== null && $row['amount'] > 0)
                ->values()
                ->all(),
            'extra_mattresses' => $this->extra_mattresses,
            'is_movable' => $this->is_movable,
            'notes' => $this->notes,
            'extra_ids' => $this->extra_ids,
        ];
    }

    /**
     * @return array{key: string, id: null, amount: string, method: null, is_invoiced: false}
     */
    private function blankPayment(): array
    {
        return ['key' => Str::random(8), 'id' => null, 'amount' => '', 'method' => null, 'is_invoiced' => false];
    }

    public function addPayment(): void
    {
        if (count($this->payments) < 20) {
            $this->payments[] = $this->blankPayment();
        }
    }

    public function removePayment(string $key): void
    {
        $this->payments = array_values(array_filter($this->payments, fn (array $row) => $row['key'] !== $key));

        if ($this->payments === []) {
            $this->payments = [$this->blankPayment()];
        }
    }

    /**
     * Señado or pagado always shows at least one row to type the payment in.
     */
    public function updatedPaymentStatus(): void
    {
        if ($this->payments === []) {
            $this->payments = [$this->blankPayment()];
        }
    }

    /**
     * @return list<PaymentLine>
     */
    protected function paymentLines(): array
    {
        return array_map(fn (array $row) => new PaymentLine(
            Format::parseMoney($row['amount']),
            PaymentMethod::tryFrom((string) ($row['method'] ?? '')),
        ), $this->payments);
    }

    /**
     * What the payment section shows under the rows: received so far and,
     * when señada with a total, what's left.
     *
     * @return array{paid: float, total: float|null, balance: float|null}
     */
    #[Computed]
    public function paymentSummary(): array
    {
        $paid = PaymentLine::sum($this->paymentLines());
        $total = PaymentStatus::from($this->payment_status)->hasTotal() ? Format::parseMoney($this->total_amount) : null;
        $total = $total !== null && $total > 0 ? $total : null;

        return ['paid' => $paid, 'total' => $total, 'balance' => $total !== null ? $total - $paid : null];
    }

    #[Computed]
    public function apartment(): ?Apartment
    {
        return $this->apartment_id ? Apartment::find($this->apartment_id) : null;
    }

    /**
     * Active employees, plus the current responsible if they were deactivated
     * (an existing reservation keeps its responsible).
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function employees(): Collection
    {
        return User::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $this->employee_id))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Extra>
     */
    #[Computed]
    public function extras(): Collection
    {
        return Extra::query()
            ->where(fn ($query) => $query->where('is_active', true)->orWhereIn('id', $this->extra_ids))
            ->orderBy('name')
            ->get();
    }

    /**
     * Shown live while editing the conditions.
     */
    #[Computed]
    public function capacityWarning(): ?Warning
    {
        return $this->apartment
            ? app(ReservationWarnings::class)->capacity($this->apartment, $this->people_count, $this->extra_mattresses)
            : null;
    }

    /**
     * @return list<Warning>
     */
    #[Computed]
    public function warnings(): array
    {
        if (! $this->apartment) {
            return [];
        }

        return app(ReservationWarnings::class)->forNew(
            apartment: $this->apartment,
            people: $this->people_count,
            extraMattresses: $this->extra_mattresses,
            paymentStatus: PaymentStatus::from($this->payment_status),
            payments: $this->paymentLines(),
            totalAmount: Format::parseMoney($this->total_amount),
            newGuest: ($this->creatingGuest ?? false) ? $this->guest : null,
        );
    }

    /**
     * The system warns, it doesn't block: the first save shows the warnings
     * and the second one ("Guardar igual") goes ahead. Returns whether to stop.
     */
    protected function stopForWarnings(): bool
    {
        $signature = collect($this->warnings)->pluck('code')->sort()->join(',');

        if ($signature !== '' && $signature !== $this->acceptedWarnings) {
            $this->acceptedWarnings = $signature;

            return true;
        }

        return false;
    }

    public function isConfirmingWarnings(): bool
    {
        $signature = collect($this->warnings)->pluck('code')->sort()->join(',');

        return $signature !== '' && $signature === $this->acceptedWarnings;
    }

    public function toggleExtra(int $extraId): void
    {
        $this->extra_ids = in_array($extraId, $this->extra_ids, true)
            ? array_values(array_diff($this->extra_ids, [$extraId]))
            : [...$this->extra_ids, $extraId];
    }

    public function addMattress(): void
    {
        $this->extra_mattresses = min(6, $this->extra_mattresses + 1);
    }

    public function removeMattress(): void
    {
        $this->extra_mattresses = max(0, $this->extra_mattresses - 1);
    }
}
