<?php

namespace App\Queries\Availability;

use App\Domain\CapacityFit;

final readonly class SuggestedApartment
{
    public function __construct(
        public ApartmentAvailability $availability,
        public CapacityFit $fit,
        public int $rank,
    ) {}

    public function isBest(): bool
    {
        return $this->rank === 1;
    }
}
