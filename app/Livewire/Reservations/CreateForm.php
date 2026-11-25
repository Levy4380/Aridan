<?php

namespace App\Livewire\Reservations;

use App\Actions\Reservations\CreateReservation;
use App\Domain\StayRange;
use App\Livewire\Concerns\EditsReservationDetails;
use App\Livewire\Concerns\PicksGuest;
use App\Models\Guest;
use App\Queries\ApartmentSuggestions;
use App\Queries\Availability\AvailabilitySearch;
use App\Queries\Availability\SuggestedApartment;
use Illuminate\Contracts\View\View;
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
    use EditsReservationDetails, PicksGuest;

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

    #[On('open-reservation-form')]
    public function open(string $checkIn, string $checkOut, bool $early, bool $late, int $people, int $apartmentId, ?int $guestId = null): void
    {
        $this->resetValidation();
        $this->resetGuestPicker();
        $this->resetDetails();

        $this->check_in_date = $checkIn;
        $this->check_out_date = $checkOut;
        $this->early_check_in = $early;
        $this->late_check_out = $late;
        $this->people_count = max(1, $people);
        $this->apartment_id = $apartmentId;

        if ($guestId && Guest::whereKey($guestId)->exists()) {
            $this->selectGuest($guestId);
        }

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

    public function chooseApartment(int $apartmentId): void
    {
        $this->apartment_id = $apartmentId;
    }

    public function save(CreateReservation $createReservation)
    {
        if (! $this->guest_id && ! $this->creatingGuest) {
            $this->addError('guest_id', __('reservations.errors.guest_required'));

            return null;
        }

        if ($this->stopForWarnings()) {
            return null;
        }

        try {
            $reservation = $createReservation([
                'apartment_id' => $this->apartment_id,
                'check_in_date' => $this->check_in_date,
                'check_out_date' => $this->check_out_date,
                'early_check_in' => $this->early_check_in,
                'late_check_out' => $this->late_check_out,
                'people_count' => $this->people_count,
                ...$this->detailsPayload(),
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

        $this->saveInvoiced($reservation);

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
        ]);
    }
}
