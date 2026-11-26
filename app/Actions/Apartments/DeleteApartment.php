<?php

namespace App\Actions\Apartments;

use App\Models\Apartment;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Deletes an apartment for good (no soft delete), together with its past
 * and cancelled reservations and their payments.
 *
 * Blocks like DeactivateApartment: with future reservations (including one
 * in progress) it can't be deleted. Cancelled reservations don't count.
 */
class DeleteApartment
{
    public function __invoke(Apartment $apartment): void
    {
        DB::transaction(function () use ($apartment) {
            // Lock the row so nobody books it while we check.
            Apartment::lockForUpdate()->findOrFail($apartment->id);

            $future = $apartment->futureReservations()->count();

            if ($future > 0) {
                throw ValidationException::withMessages([
                    'delete' => trans_choice('apartments.delete.blocked', $future, ['count' => $future]),
                ]);
            }

            // Payments and extras go with each reservation (cascade).
            Reservation::withTrashed()->where('apartment_id', $apartment->id)->forceDelete();

            $apartment->delete();
        });
    }
}
