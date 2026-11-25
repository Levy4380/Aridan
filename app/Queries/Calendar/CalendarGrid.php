<?php

namespace App\Queries\Calendar;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Everything the calendar needs to paint, already computed: the view does
 * not compute dates or rules.
 */
final readonly class CalendarGrid
{
    /**
     * @param  list<CalendarDay>  $days
     * @param  list<CalendarRow>  $rows
     * @param  list<int>  $freeByNight  free active apartments per night, aligned with $days
     * @param  Collection<int, User>  $employees  for the legend
     */
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public array $days,
        public array $rows,
        public array $freeByNight,
        public int $activeApartments,
        public int $reservationCount,
        public Collection $employees,
    ) {}

    public function slotCount(): int
    {
        return count($this->days) * 2;
    }
}
