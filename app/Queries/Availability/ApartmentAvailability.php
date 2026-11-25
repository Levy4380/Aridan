<?php

namespace App\Queries\Availability;

use App\Models\Apartment;
use App\Models\Reservation;

/**
 * One apartment's situation for the requested stay.
 */
final readonly class ApartmentAvailability
{
    /**
     * @param  list<Reservation>  $conflicts  reservations that overlap the stay
     * @param  int  $freeSlotsBefore  free half-days right before the stay (capped)
     * @param  int  $freeSlotsAfter  free half-days right after the stay (capped)
     * @param  list<OccupancyDay>  $map
     */
    public function __construct(
        public Apartment $apartment,
        public array $conflicts,
        public int $freeSlotsBefore,
        public int $freeSlotsAfter,
        public array $map,
    ) {}

    public function isFree(): bool
    {
        return $this->conflicts === [];
    }

    /**
     * Occupied, but only by reservations that can be moved elsewhere.
     */
    public function isFreeableByMoving(): bool
    {
        return ! $this->isFree()
            && collect($this->conflicts)->every(fn (Reservation $reservation) => $reservation->is_movable);
    }

    public function freeDaysBefore(): int
    {
        return intdiv($this->freeSlotsBefore, 2);
    }

    public function freeDaysAfter(): int
    {
        return intdiv($this->freeSlotsAfter, 2);
    }

    /**
     * Loose days the stay leaves around it: fewer is better.
     */
    public function fragmentation(): int
    {
        return $this->freeSlotsBefore + $this->freeSlotsAfter;
    }
}
