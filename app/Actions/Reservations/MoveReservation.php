<?php

namespace App\Actions\Reservations;

use App\Actions\Reservations\Concerns\ValidatesReservations;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves a reservation to another apartment, keeping its dates. Blocks when
 * the destination is inactive or taken. Moving a non-movable reservation is
 * a warning (not_movable) the employee confirms before calling this.
 */
class MoveReservation
{
    use ValidatesReservations;

    public function __invoke(Reservation $reservation, int $apartmentId): Reservation
    {
        if ($reservation->trashed()) {
            throw ValidationException::withMessages(['reservation' => __('reservations.errors.cancelled')]);
        }

        if ($apartmentId === $reservation->apartment_id) {
            throw ValidationException::withMessages(['reservation' => __('reservations.errors.same_apartment')]);
        }

        return $this->guardingOverlap(fn () => DB::transaction(function () use ($reservation, $apartmentId) {
            $this->ensureApartmentCanTake($apartmentId, $reservation->stay(), $reservation->id);

            $reservation->update(['apartment_id' => $apartmentId]);

            return $reservation->refresh();
        }));
    }
}
