<?php

namespace App\Actions\Reservations;

use App\Actions\Reservations\Concerns\ValidatesReservations;
use App\Domain\StayRange;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a reservation, and the guest too when it's a new one.
 *
 * Blocks (errors) only on: overlap with another active reservation,
 * inactive apartment, check-out not after check-in, a new guest that
 * repeats a phone or DNI/CUIT, or an inactive responsible employee.
 * Everything else is a warning, computed before calling this action.
 * Never trusts the UI: everything is validated again here.
 */
class CreateReservation
{
    use ValidatesReservations;

    /**
     * @param  array<string, mixed>  $input
     * @param  User|null  $by  who loads it: recorded when a payment is marked as invoiced
     */
    public function __invoke(array $input, ?User $by = null): Reservation
    {
        $data = $this->validateReservation($input);
        $by ??= auth()->user();

        return $this->guardingOverlap(fn () => DB::transaction(function () use ($data, $by) {
            $stay = StayRange::make($data['check_in_date'], $data['check_out_date'], $data['early_check_in'], $data['late_check_out']);
            $this->ensureApartmentCanTake($data['apartment_id'], $stay);

            $guestId = $data['guest_id'] ?? Guest::create($data['guest'])->id;

            $reservation = Reservation::create([
                ...collect($data)->except(['guest', 'guest_id', 'extra_ids', 'payments'])->all(),
                'guest_id' => $guestId,
            ]);

            $reservation->extras()->sync($data['extra_ids']);
            $this->syncPayments($reservation, $data['payments'], $by);

            return $reservation;
        }));
    }
}
