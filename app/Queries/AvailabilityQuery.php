<?php

namespace App\Queries;

use App\Domain\StayRange;
use App\Models\Apartment;
use App\Models\Reservation;
use App\Queries\Availability\ApartmentAvailability;
use App\Queries\Availability\AvailabilitySearch;
use App\Queries\Availability\OccupancyDay;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Support\Collection;

/**
 * For every apartment: whether it's free for the stay, what occupies it,
 * the free half-days right before and after (counted across months), and
 * an occupancy map around the requested dates. Reads only.
 */
class AvailabilityQuery
{
    /** Free time around the stay is counted up to this many days per side. */
    public const MAX_GAP_DAYS = 60;

    /** The map starts this many days before check-in... */
    public const MAP_DAYS_BEFORE = 6;

    /** ...and is at least this long. */
    public const MAP_MIN_DAYS = 21;

    /**
     * @return Collection<int, ApartmentAvailability> keyed by apartment id, in apartment order
     */
    public function __invoke(AvailabilitySearch $search): Collection
    {
        $stay = $search->stay;
        $maxGap = self::MAX_GAP_DAYS * 2;

        [$mapFrom, $mapTo] = $this->mapWindow($stay);
        $mapStart = StayRange::slot($mapFrom, StayRange::MORNING);
        $mapEnd = StayRange::slot($mapTo->addDay(), StayRange::MORNING);

        $reservations = Reservation::query()
            ->overlappingSlots(min($stay->startSlot - $maxGap, $mapStart), max($stay->endSlot + $maxGap, $mapEnd))
            ->when($search->excludeReservationId, fn ($query, $id) => $query->whereKeyNot($id))
            ->with('guest')
            ->orderBy('start_slot')
            ->get()
            ->groupBy('apartment_id');

        return Apartment::query()->ordered()->get()->mapWithKeys(function (Apartment $apartment) use ($reservations, $stay, $maxGap, $mapFrom, $mapTo) {
            /** @var Collection<int, Reservation> $own */
            $own = $reservations->get($apartment->id, collect());

            $conflicts = $own->filter(fn (Reservation $r) => $r->start_slot < $stay->endSlot && $r->end_slot > $stay->startSlot);

            $previousEnd = $own->where('end_slot', '<=', $stay->startSlot)->max('end_slot');
            $nextStart = $own->where('start_slot', '>=', $stay->endSlot)->min('start_slot');

            return [$apartment->id => new ApartmentAvailability(
                apartment: $apartment,
                conflicts: $conflicts->values()->all(),
                freeSlotsBefore: $conflicts->isEmpty() ? min($maxGap, $stay->startSlot - ($previousEnd ?? $stay->startSlot - $maxGap)) : 0,
                freeSlotsAfter: $conflicts->isEmpty() ? min($maxGap, ($nextStart ?? $stay->endSlot + $maxGap) - $stay->endSlot) : 0,
                map: $this->map($own, $stay, $mapFrom, $mapTo),
            )];
        });
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable} first and last day (inclusive)
     */
    public function mapWindow(StayRange $stay): array
    {
        $from = CarbonImmutable::parse($stay->checkIn->toDateString())->subDays(self::MAP_DAYS_BEFORE);
        $length = max(self::MAP_MIN_DAYS, $stay->nights() + 12);

        return [$from, $from->addDays($length - 1)];
    }

    /**
     * @param  Collection<int, Reservation>  $reservations
     * @return list<OccupancyDay>
     */
    private function map(Collection $reservations, StayRange $stay, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $at = fn (int $slot) => $reservations->first(fn (Reservation $r) => $r->start_slot <= $slot && $r->end_slot > $slot);

        $status = fn (?Reservation $r) => match (true) {
            $r === null => OccupancyDay::FREE,
            $r->is_movable => OccupancyDay::MOVABLE,
            default => OccupancyDay::FIXED,
        };

        $requested = fn (int $slot) => $slot >= $stay->startSlot && $slot < $stay->endSlot;

        return collect(CarbonPeriodImmutable::create($from, $to))
            ->map(function (CarbonImmutable $date) use ($at, $status, $requested) {
                $morningSlot = StayRange::slot($date, StayRange::MORNING);
                $morning = $at($morningSlot);
                $afternoon = $at($morningSlot + 1);

                $who = collect([$morning, $afternoon])->filter()->unique('id')
                    ->map(fn (Reservation $r) => $r->guest->full_name.' ('.__($r->is_movable ? 'availability.map.movable' : 'availability.map.fixed').')');

                return new OccupancyDay(
                    date: $date,
                    morning: $status($morning),
                    afternoon: $status($afternoon),
                    morningRequested: $requested($morningSlot),
                    afternoonRequested: $requested($morningSlot + 1),
                    title: $date->locale('es')->isoFormat('ddd').' '.Format::date($date)
                        .' · '.($who->isEmpty() ? __('availability.map.free') : $who->join(', ')),
                );
            })
            ->values()
            ->all();
    }
}
