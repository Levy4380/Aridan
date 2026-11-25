<?php

namespace App\Queries\Calendar;

use App\Models\Apartment;

final readonly class CalendarRow
{
    /**
     * @param  list<ReservationBlock>  $blocks
     */
    public function __construct(
        public Apartment $apartment,
        public array $blocks,
    ) {}
}
