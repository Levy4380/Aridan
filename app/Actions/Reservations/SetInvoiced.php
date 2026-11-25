<?php

namespace App\Actions\Reservations;

use App\Models\Reservation;
use App\Models\User;

/**
 * Marks a reservation as invoiced (facturada) or not. It can always be
 * changed: whatever the payment status, and also on cancelled reservations
 * (the money was received anyway). Keeps who and when, for control.
 */
class SetInvoiced
{
    public function __invoke(Reservation $reservation, bool $invoiced, User $by): Reservation
    {
        if ($invoiced === $reservation->isInvoiced()) {
            return $reservation;
        }

        $reservation->forceFill([
            'invoiced_at' => $invoiced ? now() : null,
            'invoiced_by_id' => $invoiced ? $by->id : null,
        ])->saveQuietly();

        return $reservation->refresh();
    }
}
