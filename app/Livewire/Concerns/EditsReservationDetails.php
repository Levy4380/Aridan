<?php

namespace App\Livewire\Concerns;

use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Domain\ReservationWarnings;
use App\Domain\Warning;
use App\Models\Apartment;
use App\Models\Extra;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Format;
use Illuminate\Database\Eloquent\Collection;
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

    public ?string $payment_method = null;

    public string $amount = '';

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
        $this->payment_method = null;
        $this->amount = '';
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
        $this->payment_method = $reservation->payment_method?->value;
        $this->amount = $reservation->amount !== null ? Format::money($reservation->amount) : '';
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
            'amount' => Format::parseMoney($this->amount),
            'payment_method' => $this->payment_method ?: null,
            'extra_mattresses' => $this->extra_mattresses,
            'is_movable' => $this->is_movable,
            'notes' => $this->notes,
            'extra_ids' => $this->extra_ids,
        ];
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
            amount: Format::parseMoney($this->amount),
            paymentMethod: PaymentMethod::tryFrom((string) $this->payment_method),
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
