<?php

namespace App\Livewire\Reservations;

use App\Actions\Reservations\CancelReservation;
use App\Actions\Reservations\MoveReservation;
use App\Actions\Reservations\UpdateReservation;
use App\Domain\Warning;
use App\Livewire\Concerns\EditsReservationDetails;
use App\Livewire\Concerns\PicksGuest;
use App\Models\Reservation;
use App\Queries\ApartmentSuggestions;
use App\Queries\Availability\AvailabilitySearch;
use App\Queries\Availability\SuggestedApartment;
use App\Support\Format;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Reservation side panel (arquitectura-frontend.md, 5.2 and 5.3), over the
 * calendar. Modes: view | edit | move | cancel. The open reservation lives
 * in the URL (?reserva=123), so a link opens it directly.
 */
class Panel extends Component
{
    use EditsReservationDetails, PicksGuest;

    public const VIEW = 'view';

    public const EDIT = 'edit';

    public const MOVE = 'move';

    public const CANCEL = 'cancel';

    #[Url(as: 'reserva')]
    public ?int $reservationId = null;

    public bool $show = false;

    public string $mode = self::VIEW;

    /** Success notice shown in view mode after moving or cancelling. */
    public ?string $notice = null;

    // Edit mode: what the shared traits need.
    public ?int $apartment_id = null;

    public int $people_count = 1;

    // Move mode.
    public ?int $moveTo = null;

    public bool $moveAnyway = false;

    public function mount(): void
    {
        $this->show = $this->reservationId !== null && $this->reservation !== null;

        if (! $this->show) {
            $this->reservationId = null;
        }
    }

    #[On('open-reservation')]
    public function open(int $id): void
    {
        $this->reservationId = $id;
        $this->mode = self::VIEW;
        $this->notice = null;
        $this->resetValidation();
        unset($this->reservation);
        $this->show = $this->reservation !== null;
    }

    public function updatedShow(bool $show): void
    {
        if (! $show) {
            $this->reservationId = null;
            $this->mode = self::VIEW;
            $this->notice = null;
        }
    }

    #[Computed]
    public function reservation(): ?Reservation
    {
        return $this->reservationId
            ? Reservation::withTrashed()
                ->with(['apartment', 'guest' => fn ($query) => $query->withCount('reservations'), 'employee', 'extras', 'cancelledBy'])
                ->find($this->reservationId)
            : null;
    }

    /** A guest, apartment or employee shown in the panel was edited in another drawer. */
    #[On('guest-changed')]
    #[On('apartment-changed')]
    #[On('employee-changed')]
    public function refresh(): void
    {
        unset($this->reservation);
    }

    public function back(): void
    {
        $this->mode = self::VIEW;
        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | Edit: what doesn't change the occupancy
    |--------------------------------------------------------------------------
    */

    public function startEdit(): void
    {
        $reservation = $this->reservation;

        if (! $reservation || $reservation->trashed()) {
            return;
        }

        $this->resetValidation();
        $this->resetGuestPicker();
        $this->fillDetailsFrom($reservation);
        $this->guest_id = $reservation->guest_id;
        $this->apartment_id = $reservation->apartment_id;
        $this->people_count = $reservation->people_count;
        $this->notice = null;
        $this->mode = self::EDIT;
    }

    public function save(UpdateReservation $updateReservation): void
    {
        $reservation = $this->reservation;

        if (! $this->guest_id && ! $this->creatingGuest) {
            $this->addError('guest_id', __('reservations.errors.guest_required'));

            return;
        }

        if ($this->stopForWarnings()) {
            return;
        }

        $updateReservation($reservation, [
            'apartment_id' => $reservation->apartment_id,
            'check_in_date' => $reservation->check_in_date->toDateString(),
            'check_out_date' => $reservation->check_out_date->toDateString(),
            'early_check_in' => $reservation->early_check_in,
            'late_check_out' => $reservation->late_check_out,
            'people_count' => $reservation->people_count,
            ...$this->detailsPayload(),
            ...$this->guestPayload(),
        ]);

        $this->afterChange(__('reservations.panel.updated'));
    }

    /*
    |--------------------------------------------------------------------------
    | Move to another apartment, same dates
    |--------------------------------------------------------------------------
    */

    public function startMove(): void
    {
        if (! $this->reservation || $this->reservation->trashed()) {
            return;
        }

        $this->resetValidation();
        $this->moveTo = null;
        $this->moveAnyway = false;
        $this->notice = null;
        unset($this->moveOptions);
        $this->mode = self::MOVE;
    }

    /**
     * Free apartments for the same dates, ordered like in the search,
     * ignoring the reservation itself.
     *
     * @return list<SuggestedApartment>
     */
    #[Computed]
    public function moveOptions(): array
    {
        $reservation = $this->reservation;

        if (! $reservation) {
            return [];
        }

        $suggestions = app(ApartmentSuggestions::class)(new AvailabilitySearch($reservation->stay(), $reservation->people_count, $reservation->id));

        return collect($suggestions->available)
            ->reject(fn (SuggestedApartment $option) => $option->availability->apartment->id === $reservation->apartment_id)
            ->values()
            ->all();
    }

    public function move(MoveReservation $moveReservation): void
    {
        $reservation = $this->reservation;
        $this->resetValidation();

        // Moving a non-movable reservation is a warning, confirmed with the checkbox.
        if (! $reservation->is_movable && ! $this->moveAnyway) {
            $this->addError('moveAnyway', __('warnings.'.Warning::NOT_MOVABLE));

            return;
        }

        if (! $this->moveTo) {
            $this->addError('moveTo', __('reservations.panel.choose_apartment'));

            return;
        }

        $from = $reservation->apartment->number;
        $moved = $moveReservation($reservation, $this->moveTo);

        $this->afterChange(__('reservations.panel.moved', [
            'to' => $moved->apartment->number,
            'from' => $from,
            'dates' => Format::date($moved->check_in_date).' al '.Format::date($moved->check_out_date),
        ]));
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel
    |--------------------------------------------------------------------------
    */

    public function startCancel(): void
    {
        if (! $this->reservation || $this->reservation->trashed()) {
            return;
        }

        $this->notice = null;
        $this->mode = self::CANCEL;
    }

    public function cancel(CancelReservation $cancelReservation): void
    {
        $cancelReservation($this->reservation, auth()->user());

        $this->afterChange(null);
    }

    private function afterChange(?string $notice): void
    {
        unset($this->reservation, $this->moveOptions);
        $this->mode = self::VIEW;
        $this->notice = $notice;
        $this->dispatch('reservation-changed');
    }

    public function render(): View
    {
        return view('livewire.reservations.panel');
    }
}
