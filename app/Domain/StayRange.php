<?php

namespace App\Domain;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * A stay as a half-open range of half-day slots [startSlot, endSlot).
 *
 * The minimum unit is the half day: morning (M) or afternoon (T). A slot is
 *
 *     slot(date, half) = daysSinceEpoch(date) * 2 + (half == morning ? 0 : 1)
 *
 * A normal stay takes from the afternoon of the check-in day to the morning
 * of the check-out day, so a morning checkout and an afternoon check-in share
 * the same day. Early check-in also takes the check-in morning; late
 * check-out also takes the check-out afternoon.
 *
 * Two stays of the same apartment overlap when startA < endB && startB < endA.
 */
final readonly class StayRange
{
    public const MORNING = 0;

    public const AFTERNOON = 1;

    public int $startSlot;

    public int $endSlot;

    public function __construct(
        public CarbonImmutable $checkIn,
        public CarbonImmutable $checkOut,
        public bool $earlyCheckIn = false,
        public bool $lateCheckOut = false,
    ) {
        if ($checkOut->startOfDay()->lessThanOrEqualTo($checkIn->startOfDay())) {
            throw new InvalidArgumentException('The check-out date must be after the check-in date.');
        }

        $this->startSlot = self::slot($checkIn, $earlyCheckIn ? self::MORNING : self::AFTERNOON);
        $this->endSlot = self::slot($checkOut, $lateCheckOut ? self::AFTERNOON : self::MORNING) + 1;
    }

    public static function make(
        DateTimeInterface|string $checkIn,
        DateTimeInterface|string $checkOut,
        bool $earlyCheckIn = false,
        bool $lateCheckOut = false,
    ): self {
        return new self(self::date($checkIn), self::date($checkOut), $earlyCheckIn, $lateCheckOut);
    }

    public static function slot(DateTimeInterface|string $date, int $half): int
    {
        return self::dayNumber($date) * 2 + $half;
    }

    /**
     * Days since 1970-01-01, ignoring time and timezone.
     */
    public static function dayNumber(DateTimeInterface|string $date): int
    {
        return intdiv(self::date($date)->utc()->getTimestamp(), 86400);
    }

    /**
     * The calendar date a slot belongs to.
     */
    public static function dateOfSlot(int $slot): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestampUTC(intdiv($slot, 2) * 86400)->startOfDay();
    }

    public function overlaps(self $other): bool
    {
        return $this->startSlot < $other->endSlot && $other->startSlot < $this->endSlot;
    }

    public function nights(): int
    {
        return (int) $this->checkIn->startOfDay()->diffInDays($this->checkOut->startOfDay());
    }

    private static function date(DateTimeInterface|string $date): CarbonImmutable
    {
        $value = $date instanceof DateTimeInterface ? $date->format('Y-m-d') : substr($date, 0, 10);

        return CarbonImmutable::createFromFormat('!Y-m-d', $value, 'UTC');
    }
}
