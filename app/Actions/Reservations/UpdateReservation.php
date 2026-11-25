<?php

namespace App\Actions\Reservations;

use App\Actions\Reservations\Concerns\ValidatesReservations;
use App\Domain\StayRange;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Edits a reservation. Same blocking rules as CreateReservation, checking
 * the overlap against every other reservation. A responsible employee who
 * was deactivated can stay as is; a new one has to be active.
 */
class UpdateReservation
{
    use ValidatesReservations;

    /**
     * @param  array<string, mixed>  $input  the full reservation payload (as for CreateReservation)
     * @param  User|null  $by  who edits it: recorded when a payment is marked as invoiced
     */
    public function __invoke(Reservation $reservation, array $input, ?User $by = null): Reservation
    {
        $by ??= auth()->user();

        if ($reservation->trashed()) {
            throw ValidationException::withMessages(['reservation' => __('reservations.errors.cancelled')]);
        }

        $data = $this->validateReservation($input, $reservation);

        return $this->guardingOverlap(fn () => DB::transaction(function () use ($reservation, $data, $by) {
            $stay = StayRange::make($data['check_in_date'], $data['check_out_date'], $data['early_check_in'], $data['late_check_out']);
            // A reservation already in an apartment deactivated later (e.g. a
            // past one) can still be edited; moving into an inactive one can't.
            $this->ensureApartmentCanTake($data['apartment_id'], $stay, $reservation->id, mustBeActive: (int) $data['apartment_id'] !== $reservation->apartment_id);

            $guestId = $data['guest_id'] ?? Guest::create($data['guest'])->id;

            $reservation->update([
                ...collect($data)->except(['guest', 'guest_id', 'extra_ids', 'payments'])->all(),
                'guest_id' => $guestId,
            ]);

            $reservation->extras()->sync($data['extra_ids']);
            $this->syncPayments($reservation, $data['payments'], $by);

            return $reservation->refresh();
        }));
    }
}
