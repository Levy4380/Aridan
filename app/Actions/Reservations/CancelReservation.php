<?php

namespace App\Actions\Reservations;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cancels a reservation: it stays in the database for control (who and
 * when) but no longer occupies the apartment. There is no "undo".
 */
class CancelReservation
{
    public function __invoke(Reservation $reservation, User $cancelledBy): Reservation
    {
        if ($reservation->trashed()) {
            return $reservation;
        }

        DB::transaction(function () use ($reservation, $cancelledBy) {
            $reservation->cancelled_by_id = $cancelledBy->id;
            $reservation->save();
            $reservation->delete();
        });

        return $reservation->refresh();
    }
}
