<?php

namespace App\Actions\Guests;

use App\Models\Guest;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Deletes a guest for good, e.g. a duplicate or one loaded by mistake. Only
 * without reservations, cancelled ones included: a reservation can't be
 * left without its guest.
 */
class DeleteGuest
{
    public function __invoke(Guest $guest): void
    {
        DB::transaction(function () use ($guest) {
            Guest::lockForUpdate()->findOrFail($guest->id);

            $count = Reservation::withTrashed()->where('guest_id', $guest->id)->count();

            if ($count > 0) {
                throw ValidationException::withMessages([
                    'delete' => trans_choice('guests.delete.blocked', $count, ['count' => $count]),
                ]);
            }

            $guest->delete();
        });
    }
}
