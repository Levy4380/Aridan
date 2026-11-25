<?php

namespace App\Queries;

use App\Domain\StayRange;
use App\Models\Apartment;
use App\Models\Holiday;
use App\Models\Reservation;
use App\Models\User;
use App\Queries\Calendar\CalendarDay;
use App\Queries\Calendar\CalendarGrid;
use App\Queries\Calendar\CalendarRow;
use App\Queries\Calendar\ReservationBlock;
use App\Support\ColorContrast;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The calendar is just a query over any window of dates: reservations that
 * touch the window (start_slot < window_end && end_slot > window_start),
 * cut at its edges. There is no month logic.
 */
class CalendarQuery
{
    /**
     * @param  CarbonImmutable  $from  first visible day
     * @param  CarbonImmutable  $to  last visible day (inclusive)
     */
    public function __invoke(CarbonImmutable $from, CarbonImmutable $to): CalendarGrid
    {
        $from = $from->startOfDay();
        $to = $to->startOfDay();

        if ($to->lessThan($from)) {
            throw new InvalidArgumentException('The window must end on or after its first day.');
        }

        $windowStart = StayRange::slot($from, StayRange::MORNING);
        $windowEnd = StayRange::slot($to->addDay(), StayRange::MORNING);

        $reservations = Reservation::query()
            ->overlappingSlots($windowStart, $windowEnd)
            ->with(['guest', 'employee', 'extras', 'apartment'])
            ->orderBy('start_slot')
            ->get();

        $byApartment = $reservations->groupBy('apartment_id');

        // Active apartments, plus inactive ones that still have reservations
        // in the window, so their history doesn't disappear.
        $apartments = Apartment::query()
            ->where(fn ($query) => $query
                ->where('is_active', true)
                ->orWhereIn('id', $byApartment->keys()))
            ->ordered()
            ->get();

        $rows = $apartments->map(fn (Apartment $apartment) => new CalendarRow(
            $apartment,
            $byApartment->get($apartment->id, collect())
                ->map(fn (Reservation $reservation) => $this->block($reservation, $apartment, $windowStart, $windowEnd))
                ->values()
                ->all(),
        ))->all();

        $days = $this->days($from, $to);

        return new CalendarGrid(
            from: $from,
            to: $to,
            days: $days,
            rows: $rows,
            freeByNight: $this->freeByNight($days, $apartments, $reservations),
            activeApartments: $apartments->where('is_active', true)->count(),
            reservationCount: $reservations->count(),
            employees: User::active()->orderBy('name')->get(),
        );
    }

    /**
     * @return list<CalendarDay>
     */
    private function days(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $holidays = Holiday::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->pluck('name', 'date')
            ->mapWithKeys(fn (string $name, $date) => [substr((string) $date, 0, 10) => $name]);

        $today = CarbonImmutable::today();

        return collect(CarbonPeriodImmutable::create($from, $to))
            ->map(fn (CarbonImmutable $date) => new CalendarDay(
                date: $date,
                isWeekend: $date->isWeekend(),
                isToday: $date->isSameDay($today),
                holiday: $holidays->get($date->toDateString()),
            ))
            ->values()
            ->all();
    }

    private function block(Reservation $reservation, Apartment $apartment, int $windowStart, int $windowEnd): ReservationBlock
    {
        $start = max($reservation->start_slot, $windowStart);
        $end = min($reservation->end_slot, $windowEnd);

        $guestName = $reservation->guest->full_name;
        $missing = max(0, $reservation->people_count - $apartment->capacity);

        $peopleLine = Format::people($reservation->people_count)
            .($missing > 0 ? " (+{$missing})" : '')
            .' · '.$reservation->payment_status->label()
            .($reservation->payment_status->hasPayment() && $reservation->amount !== null ? ' '.Format::money($reservation->amount) : '');

        $employee = $reservation->employee;

        return new ReservationBlock(
            reservationId: $reservation->id,
            startColumn: $start - $windowStart + 1,
            endColumn: $end - $windowStart + 1,
            clipLeft: $reservation->start_slot < $windowStart,
            clipRight: $reservation->end_slot > $windowEnd,
            color: $employee->color,
            textColor: ColorContrast::textOn($employee->color),
            guestName: $guestName,
            peopleLine: $peopleLine,
            employeeName: $employee->name,
            isMovable: $reservation->is_movable,
            earlyCheckIn: $reservation->early_check_in,
            lateCheckOut: $reservation->late_check_out,
            extras: $reservation->extras
                ->map(fn ($extra) => ['name' => $extra->name, 'icon' => $extra->icon()])
                ->values()
                ->all(),
            ariaLabel: __('calendar.block_label', [
                'number' => $apartment->number,
                'guest' => $guestName,
                'details' => $peopleLine,
                'employee' => $employee->name,
            ]),
        );
    }

    /**
     * Active apartments free each night. Night D is the afternoon slot of D:
     * it's taken by any reservation that is there that afternoon.
     *
     * @param  list<CalendarDay>  $days
     * @param  Collection<int, Apartment>  $apartments
     * @param  Collection<int, Reservation>  $reservations
     * @return list<int>
     */
    private function freeByNight(array $days, Collection $apartments, Collection $reservations): array
    {
        $activeIds = $apartments->where('is_active', true)->pluck('id')->flip();
        $active = $reservations->filter(fn (Reservation $reservation) => $activeIds->has($reservation->apartment_id));

        return array_map(function (CalendarDay $day) use ($activeIds, $active) {
            $night = StayRange::slot($day->date, StayRange::AFTERNOON);

            $occupied = $active
                ->filter(fn (Reservation $reservation) => $reservation->start_slot <= $night && $reservation->end_slot > $night)
                ->pluck('apartment_id')
                ->unique()
                ->count();

            return $activeIds->count() - $occupied;
        }, $days);
    }
}
