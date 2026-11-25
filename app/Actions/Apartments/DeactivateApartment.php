<?php

namespace App\Actions\Apartments;

use App\Models\Apartment;

/**
 * Deactivates an apartment so it can no longer be booked.
 *
 * Business rule (CLAUDE.md): an apartment with future reservations cannot be
 * deactivated. That check is added together with the reservations table
 * (construction step 5); until then there are no reservations to block on.
 * See the pending test in tests/Feature/Apartments/ApartmentActionsTest.php.
 */
class DeactivateApartment
{
    public function __invoke(Apartment $apartment): Apartment
    {
        $apartment->update(['is_active' => false]);

        return $apartment;
    }
}
