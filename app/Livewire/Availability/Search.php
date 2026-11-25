<?php

namespace App\Livewire\Availability;

use App\Actions\Reservations\UpdateReservation;
use App\Domain\ReservationWarnings;
use App\Domain\StayRange;
use App\Domain\Warning;
use App\Models\Apartment;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Queries\ApartmentSuggestions;
use App\Queries\Availability\AvailabilitySearch;
use App\Queries\AvailabilityQuery;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Availability search (/buscador): the only entry point for a new
 * reservation. Results recalculate as the fields change; everything lives in
 * the URL so the search can be shared and "Cambiar" can come back to it.
 *
 * Edit mode (/buscador?editar=123), opened from the reservation panel:
 * starts with that reservation's data, ignores it when checking
 * availability, and "Usar este" moves it to the new dates / apartment.
 */
class Search extends Component
{
    #[Url(as: 'entrada')]
    public string $checkIn = '';

    #[Url(as: 'salida')]
    public string $checkOut = '';

    /**
     * Kept as typed (a string): while the employee erases it to write another
     * number it can be empty, and that must not break nor search.
     */
    #[Url(as: 'personas')]
    public string $people = '2';

    #[Url(as: 'temprana')]
    public bool $earlyCheckIn = false;

    #[Url(as: 'tardia')]
    public bool $lateCheckOut = false;

    /** "Nueva reserva para este cliente": the drawer opens with this guest. */
    #[Url(as: 'cliente')]
    public ?int $guestId = null;

    /** Edit mode: the reservation whose dates, people or apartment change. */
    #[Url(as: 'editar')]
    public ?int $editingId = null;

    /** Edit mode: the apartment picked with "Usar este", waiting for confirmation. */
    public ?int $confirmApartmentId = null;

    public function mount(): void
    {
        $editing = $this->editing();

        if ($editing) {
            // Start from the reservation, but what's already in the URL wins
            // (a reload keeps the changes being tried).
            $query = request()->query();
            $this->checkIn = isset($query['entrada']) ? $this->checkIn : $editing->check_in_date->toDateString();
            $this->checkOut = isset($query['salida']) ? $this->checkOut : $editing->check_out_date->toDateString();
            $this->people = isset($query['personas']) ? $this->people : (string) $editing->people_count;
            $this->earlyCheckIn = isset($query['temprana']) ? $this->earlyCheckIn : $editing->early_check_in;
            $this->lateCheckOut = isset($query['tardia']) ? $this->lateCheckOut : $editing->late_check_out;
        }

        if (! $editing) {
            $this->editingId = null;
        }

        $this->checkIn = $this->validDate($this->checkIn) ?? CarbonImmutable::today()->toDateString();
        $this->checkOut = $this->validDate($this->checkOut)
            ?? CarbonImmutable::parse($this->checkIn)->addDays(2)->toDateString();
    }

    /**
     * Keep the stay at least one night long when the check-in moves past it.
     */
    public function updatedCheckIn(): void
    {
        $in = $this->validDate($this->checkIn);
        $out = $this->validDate($this->checkOut);

        if ($in && $out && $out <= $in) {
            $this->checkOut = CarbonImmutable::parse($in)->addDay()->toDateString();
        }
    }

    #[On('reservation-changed')]
    #[On('apartment-changed')]
    public function refresh(): void
    {
        // Re-render with fresh data.
    }

    /**
     * The reservation being changed in edit mode (cancelled ones can't be).
     */
    public function editing(): ?Reservation
    {
        return $this->editingId
            ? Reservation::with(['guest', 'apartment', 'extras'])->find($this->editingId)
            : null;
    }

    /** Any change to the search drops a pending confirmation. */
    public function updated(string $property): void
    {
        if ($property !== 'confirmApartmentId') {
            $this->confirmApartmentId = null;
        }
    }

    public function chooseForEditing(int $apartmentId): void
    {
        $this->confirmApartmentId = $apartmentId;
    }

    public function cancelChange(): void
    {
        $this->confirmApartmentId = null;
    }

    /**
     * Applies the new dates / people / apartment to the reservation being
     * edited. The rest of the reservation stays as it was.
     */
    public function applyChange(UpdateReservation $updateReservation)
    {
        $reservation = $this->editing();
        $stay = $this->stay();
        $people = $this->peopleCount();

        if (! $reservation || ! $stay || ! $people || ! $this->confirmApartmentId) {
            return null;
        }

        $updateReservation($reservation, [
            'apartment_id' => $this->confirmApartmentId,
            'employee_id' => $reservation->employee_id,
            'check_in_date' => $stay->checkIn->toDateString(),
            'check_out_date' => $stay->checkOut->toDateString(),
            'early_check_in' => $stay->earlyCheckIn,
            'late_check_out' => $stay->lateCheckOut,
            'people_count' => $people,
            'payment_status' => $reservation->payment_status->value,
            'total_amount' => $reservation->total_amount,
            'payments' => $reservation->payments->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'method' => $payment->method?->value,
                'is_invoiced' => $payment->isInvoiced(),
            ])->all(),
            'extra_mattresses' => $reservation->extra_mattresses,
            'is_movable' => $reservation->is_movable,
            'notes' => $reservation->notes,
            'extra_ids' => $reservation->extras->pluck('id')->all(),
            'guest_id' => $reservation->guest_id,
            'guest' => null,
        ]);

        session()->flash('status', __('availability.edit.saved', ['guest' => $reservation->guest->full_name]));

        return $this->redirectRoute('calendar', [
            'desde' => $stay->checkIn->startOfMonth()->toDateString(),
            'reserva' => $reservation->id,
            'resaltar' => $reservation->id,
        ], navigate: true);
    }

    /**
     * Warnings for the change waiting for confirmation: capacity and, when
     * it changes apartment, a non-movable reservation. They don't block.
     *
     * @return list<Warning>
     */
    private function changeWarnings(Reservation $reservation, int $people): array
    {
        $apartment = Apartment::find($this->confirmApartmentId);

        if (! $apartment) {
            return [];
        }

        return array_values(array_filter([
            app(ReservationWarnings::class)->capacity($apartment, $people, $reservation->extra_mattresses),
            $apartment->id !== $reservation->apartment_id && ! $reservation->is_movable
                ? new Warning(Warning::NOT_MOVABLE, __('warnings.not_movable'))
                : null,
        ]));
    }

    public function render(ApartmentSuggestions $suggestions, AvailabilityQuery $availability): View
    {
        $stay = $this->stay();
        $people = $this->peopleCount();
        $searching = $stay && $people;
        $editing = $this->editing();

        return view('livewire.availability.search', [
            'stay' => $stay,
            'peopleCount' => $people,
            'suggestions' => $searching ? $suggestions(new AvailabilitySearch($stay, $people, $editing?->id)) : null,
            'mapDays' => $searching ? $this->mapDays($availability, $stay) : [],
            'forGuest' => $this->guestId ? Guest::find($this->guestId) : null,
            'editing' => $editing,
            'confirmApartment' => $editing && $this->confirmApartmentId ? Apartment::find($this->confirmApartmentId) : null,
            'changeWarnings' => $editing && $people && $this->confirmApartmentId ? $this->changeWarnings($editing, $people) : [],
        ])->title($editing ? __('availability.edit.title') : __('availability.title'));
    }

    /**
     * The number of people, or null while the field doesn't hold a number
     * greater than 0 (nothing is searched until it does).
     */
    public function peopleCount(): ?int
    {
        $people = trim($this->people);

        return ctype_digit($people) && (int) $people > 0 ? min((int) $people, 30) : null;
    }

    private function stay(): ?StayRange
    {
        $in = $this->validDate($this->checkIn);
        $out = $this->validDate($this->checkOut);

        if (! $in || ! $out || $out <= $in) {
            return null;
        }

        return StayRange::make($in, $out, $this->earlyCheckIn, $this->lateCheckOut);
    }

    /**
     * @return list<array{date: CarbonImmutable, requested: bool}>
     */
    private function mapDays(AvailabilityQuery $availability, StayRange $stay): array
    {
        [$from, $to] = $availability->mapWindow($stay);
        $days = [];

        for ($date = $from; $date->lessThanOrEqualTo($to); $date = $date->addDay()) {
            $slot = StayRange::slot($date, StayRange::MORNING);
            $days[] = [
                'date' => $date,
                'requested' => $slot + 1 >= $stay->startSlot && $slot < $stay->endSlot,
            ];
        }

        return $days;
    }

    private function validDate(string $value): ?string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value)->toDateString() === $value ? $value : null;
        } catch (Throwable) {
            return null;
        }
    }
}
