<?php

namespace App\Queries\Calendar;

/**
 * A reservation as drawn on the calendar: its position in slot columns of
 * the visible window and the three lines of the spreadsheet block.
 */
final readonly class ReservationBlock
{
    /**
     * @param  int  $startColumn  1-based slot column inside the window (inclusive)
     * @param  int  $endColumn  1-based slot column inside the window (exclusive, CSS grid line)
     * @param  list<array{name: string, icon: string|null}>  $extras
     */
    public function __construct(
        public int $reservationId,
        public int $startColumn,
        public int $endColumn,
        public bool $clipLeft,
        public bool $clipRight,
        public string $color,
        public string $textColor,
        public string $guestName,
        public string $peopleLine,
        public string $employeeName,
        public bool $isMovable,
        public bool $earlyCheckIn,
        public bool $lateCheckOut,
        public array $extras,
        public string $ariaLabel,
    ) {}
}
