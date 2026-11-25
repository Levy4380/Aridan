<?php

namespace App\Queries\Availability;

/**
 * The three groups of the availability search.
 */
final readonly class Suggestions
{
    /**
     * @param  list<SuggestedApartment>  $available  free, best option first
     * @param  list<ApartmentAvailability>  $movable  occupied only by movable reservations
     * @param  list<ApartmentAvailability>  $unavailable  occupied by a fixed reservation, or inactive
     */
    public function __construct(
        public array $available,
        public array $movable,
        public array $unavailable,
    ) {}

    public function best(): ?SuggestedApartment
    {
        return $this->available[0] ?? null;
    }
}
