<?php

use App\Domain\StayRange;
use App\Models\Apartment;
use App\Models\Reservation;
use App\Queries\Availability\AvailabilitySearch;
use App\Queries\Availability\OccupancyDay;
use App\Queries\AvailabilityQuery;

function availability(string $in, string $out, bool $early = false, bool $late = false, ?int $exclude = null)
{
    return app(AvailabilityQuery::class)(new AvailabilitySearch(StayRange::make($in, $out, $early, $late), 2, $exclude));
}

beforeEach(function () {
    $this->apartment = Apartment::factory()->create();
});

test('an apartment with no reservations is free with the maximum gap on both sides', function () {
    $result = availability('2026-11-10', '2026-11-12')->get($this->apartment->id);

    expect($result->isFree())->toBeTrue()
        ->and($result->freeDaysBefore())->toBe(AvailabilityQuery::MAX_GAP_DAYS)
        ->and($result->freeDaysAfter())->toBe(AvailabilityQuery::MAX_GAP_DAYS);
});

test('free days are counted up to the neighbouring reservations', function () {
    Reservation::factory()->for($this->apartment)->between('2026-11-05', '2026-11-08')->create();
    Reservation::factory()->for($this->apartment)->between('2026-11-16', '2026-11-20')->create();

    $result = availability('2026-11-10', '2026-11-12')->get($this->apartment->id);

    // Leaves the 8th morning, we arrive the 10th afternoon: 2 days. We leave
    // the 12th morning, the next arrives the 16th afternoon: 4 days.
    expect($result->freeDaysBefore())->toBe(2)
        ->and($result->freeDaysAfter())->toBe(4)
        ->and($result->fragmentation())->toBe(12);
});

test('back-to-back turnovers leave no loose days', function () {
    Reservation::factory()->for($this->apartment)->between('2026-11-05', '2026-11-10')->create();
    Reservation::factory()->for($this->apartment)->between('2026-11-12', '2026-11-15')->create();

    $result = availability('2026-11-10', '2026-11-12')->get($this->apartment->id);

    expect($result->isFree())->toBeTrue()
        ->and($result->fragmentation())->toBe(0);
});

test('free days are counted across months', function () {
    Reservation::factory()->for($this->apartment)->between('2026-12-28', '2027-01-02')->create();

    $result = availability('2027-01-05', '2027-01-07')->get($this->apartment->id);

    expect($result->freeDaysBefore())->toBe(3);
});

test('overlapping reservations are reported as conflicts', function () {
    $existing = Reservation::factory()->for($this->apartment)->between('2026-11-11', '2026-11-14')->create();

    $result = availability('2026-11-10', '2026-11-12')->get($this->apartment->id);

    expect($result->isFree())->toBeFalse()
        ->and(collect($result->conflicts)->pluck('id')->all())->toBe([$existing->id]);
});

test('early check-in collides with a checkout that same morning', function () {
    Reservation::factory()->for($this->apartment)->between('2026-11-05', '2026-11-10')->create();

    expect(availability('2026-11-10', '2026-11-12')->get($this->apartment->id)->isFree())->toBeTrue()
        ->and(availability('2026-11-10', '2026-11-12', early: true)->get($this->apartment->id)->isFree())->toBeFalse();
});

test('cancelled and excluded reservations do not occupy the apartment', function () {
    Reservation::factory()->for($this->apartment)->between('2026-11-10', '2026-11-12')->create()->delete();
    $editing = Reservation::factory()->for($this->apartment)->between('2026-11-10', '2026-11-12')->create();

    expect(availability('2026-11-10', '2026-11-12')->get($this->apartment->id)->isFree())->toBeFalse()
        ->and(availability('2026-11-10', '2026-11-12', exclude: $editing->id)->get($this->apartment->id)->isFree())->toBeTrue();
});

test('the occupancy map covers the window around the stay, by half day', function () {
    Reservation::factory()->for($this->apartment)->between('2026-11-05', '2026-11-08')->fixed()->create();
    Reservation::factory()->for($this->apartment)->between('2026-11-12', '2026-11-14')->create(); // movable

    $map = collect(availability('2026-11-10', '2026-11-12')->get($this->apartment->id)->map)
        ->keyBy(fn (OccupancyDay $day) => $day->date->toDateString());

    expect($map)->toHaveCount(AvailabilityQuery::MAP_MIN_DAYS)
        ->and($map->keys()->first())->toBe('2026-11-04')
        ->and($map['2026-11-08']->morning)->toBe(OccupancyDay::FIXED)
        ->and($map['2026-11-08']->afternoon)->toBe(OccupancyDay::FREE)
        ->and($map['2026-11-10']->morningRequested)->toBeFalse()
        ->and($map['2026-11-10']->afternoonRequested)->toBeTrue()
        ->and($map['2026-11-12']->morningRequested)->toBeTrue()
        ->and($map['2026-11-12']->afternoon)->toBe(OccupancyDay::MOVABLE);
});
