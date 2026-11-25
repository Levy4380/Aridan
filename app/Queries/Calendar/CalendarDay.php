<?php

namespace App\Queries\Calendar;

use Carbon\CarbonImmutable;

final readonly class CalendarDay
{
    public function __construct(
        public CarbonImmutable $date,
        public bool $isWeekend,
        public bool $isToday,
        public ?string $holiday,
    ) {}
}
