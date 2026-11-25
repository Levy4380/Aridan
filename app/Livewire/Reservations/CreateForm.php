<?php

namespace App\Livewire\Reservations;

use App\Actions\Reservations\CreateReservation;
use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Domain\ReservationWarnings;
use App\Domain\StayRange;
use App\Domain\Warning;
use App\Livewire\Concerns\PicksGuest;
use App\Models\Apartment;
use App\Models\Extra;
use App\Models\User;
use App\Queries\ApartmentSuggestions;
use App\Queries\Availability\AvailabilitySearch;
use App\Queries\Availability\SuggestedApartment;
use App\Support\Format;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Nueva reserva" modal (arquitectura-frontend.md, 5.4). Opened from the
 * search with the dates, people and flags already fixed; "Cambiar" goes
 * back to the search untouched. Only the minimum fields are required.
 */
class CreateForm extends Component
{
    use PicksGuest;

    public bool $show = false;

    // Fixed by the search: changing them means going back to it.
    #[Locked]
    public string $check_in_date = '';

    #[Locked]
    public string $check_out_date = '';

    #[Locked]
    public bool $early_check_in = false;

    #[Locked]
    public bool $late_check_out = false;

    #[Locked]
    public int $people_count = 1;

    public ?int $apartment_id = null;

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

    #[On('open-reservation-form')]
    public function open(string $checkIn, string $checkOut, bool $early, bool $late, int $people, int $apartmentId): void
    {
        $this->resetValidation();
        $this->resetGuestPicker();
        $this->reset(['payment_method', 'amount', 'is_movable', 'extra_ids', 'extra_mattresses', 'notes', 'acceptedWarnings']);

        $this->check_in_date = $checkIn;
        $this->check_out_date = $checkOut;
        $this->early_check_in = $early;
        $this->late_check_out = $late;
        $this->people_count = max(1, $people);
        $this->apartment_id = $apartmentId;
        $this->employee_id = auth()->id();
        $this->payment_status = PaymentStatus::Deposit->value;

        unset($this->options);
        $this->show = true;
    }

    public function stay(): ?StayRange
    {
        return $this->check_in_date && $this->check_out_date
            ? StayRange::make($this->check_in_date, $this->check_out_date, $this->early_check_in, $this->late_check_out)
            : null;
    }

    /**
     * Three apartments to choose from: the chosen one plus the best others,
     * in ranking order.
     *
     * @return list<SuggestedApartment>
     */
    #[Computed]
    public function options(): array
    {
        $stay = $this->stay();

        if (! $stay) {
            return [];
        }

        $available = collect(app(ApartmentSuggestions::class)(new AvailabilitySearch($stay, $this->people_count))->available);
        $chosen = $available->first(fn (SuggestedApartment $option) => $option->availability->apartment->id === $this->apartment_id);

        return $available
            ->reject(fn (SuggestedApartment $option) => $option === $chosen)
            ->take($chosen ? 2 : 3)
            ->when($chosen, fn ($options) => $options->push($chosen))
            ->sortBy('rank')
            ->values()
            ->all();
    }

    #[Computed]
    public function apartment(): ?Apartment
    {
        return $this->apartment_id ? Apartment::find($this->apartment_id) : null;
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function employees(): Collection
    {
        return User::active()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Extra>
     */
    #[Computed]
    public function extras(): Collection
    {
        return Extra::where('is_active', true)->orderBy('name')->get();
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
            newGuest: $this->creatingGuest ? $this->guest : null,
        );
    }

    public function chooseApartment(int $apartmentId): void
    {
        $this->apartment_id = $apartmentId;
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

    public function save(CreateReservation $createReservation)
    {
        if (! $this->guest_id && ! $this->creatingGuest) {
            $this->addError('guest_id', __('reservations.errors.guest_required'));

            return null;
        }

        // The system warns, it doesn't block: warnings need one confirmation.
        $signature = collect($this->warnings)->pluck('code')->sort()->join(',');

        if ($signature !== '' && $signature !== $this->acceptedWarnings) {
            $this->acceptedWarnings = $signature;

            return null;
        }

        try {
            $reservation = $createReservation([
                'apartment_id' => $this->apartment_id,
                'employee_id' => $this->employee_id,
                'check_in_date' => $this->check_in_date,
                'check_out_date' => $this->check_out_date,
                'early_check_in' => $this->early_check_in,
                'late_check_out' => $this->late_check_out,
                'people_count' => $this->people_count,
                'payment_status' => $this->payment_status,
                'amount' => Format::parseMoney($this->amount),
                'payment_method' => $this->payment_method ?: null,
                'extra_mattresses' => $this->extra_mattresses,
                'is_movable' => $this->is_movable,
                'notes' => $this->notes,
                'extra_ids' => $this->extra_ids,
                ...$this->guestPayload(),
            ]);
        } catch (ValidationException $e) {
            // An overlap means someone else just booked it: refresh the options.
            if (array_key_exists('reservation', $e->errors())) {
                unset($this->options);
                $this->dispatch('reservation-changed');
            }

            throw $e;
        }

        session()->flash('status', __('reservations.created', [
            'guest' => $reservation->guest->full_name,
            'number' => $reservation->apartment->number,
        ]));

        return $this->redirectRoute('calendar', [
            'desde' => $reservation->check_in_date->startOfMonth()->toDateString(),
            'resaltar' => $reservation->id,
        ], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.reservations.create-form', [
            'stay' => $this->stay(),
            'pendingWarnings' => $this->acceptedWarnings !== null ? $this->warnings : [],
        ]);
    }
}
