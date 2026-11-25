<?php

namespace App\Actions\Apartments;

use App\Models\Apartment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Deactivates an apartment so it can no longer be booked.
 *
 * One of the two hard blocks of the system (CLAUDE.md): an apartment with
 * future reservations (including one in progress) cannot be deactivated.
 * Cancelled reservations don't count.
 */
class DeactivateApartment
{
    public function __invoke(Apartment $apartment): Apartment
    {
        return DB::transaction(function () use ($apartment) {
            // Lock the row so nobody books it while we check.
            Apartment::lockForUpdate()->findOrFail($apartment->id);

            $future = $apartment->futureReservations()->count();

            if ($future > 0) {
                throw ValidationException::withMessages([
                    'is_active' => trans_choice('apartments.cannot_deactivate', $future, ['count' => $future]),
                ]);
            }

            $apartment->update(['is_active' => false]);

            return $apartment;
        });
    }
}
