<?php

namespace App\Actions\Apartments;

use App\Models\Apartment;

class ActivateApartment
{
    public function __invoke(Apartment $apartment): Apartment
    {
        $apartment->update(['is_active' => true]);

        return $apartment;
    }
}
