<?php

namespace App\Queries\Availability;

use App\Domain\StayRange;

/**
 * What the employee is looking for: dates, half-day flags and people.
 */
final readonly class AvailabilitySearch
{
    public function __construct(
        public StayRange $stay,
        public int $people,
        public ?int $excludeReservationId = null,
    ) {}
}
