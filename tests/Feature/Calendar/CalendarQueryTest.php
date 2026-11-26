<?php

use App\Domain\PaymentMethod;
use App\Models\Apartment;
use App\Models\Extra;
use App\Models\Guest;
use App\Models\Holiday;
use App\Models\Reservation;
use App\Models\User;
use App\Queries\CalendarQuery;
use Carbon\CarbonImmutable;

function calendar(string $from, string $to)
{
    return app(CalendarQuery::class)(CarbonImmutable::parse($from), CarbonImmutable::parse($to));
}

beforeEach(function () {
    $this->employee = User::factory()->create(['name' => 'Tomás', 'color' => '#4fc3f7']);
    $this->apartment = Apartment::factory()->create(['number' => '105', 'capacity' => 3, 'sort_order' => 1]);
});

test('a reservation is placed on its slot columns with the spreadsheet lines', function () {

    config(['extras.enabled' => true]);
    $guest = Guest::factory()->create(['first_name' => 'Martín', 'last_name' => 'Medina']);
    $reservation = Reservation::factory()
        ->for($this->apartment)->for($guest)->for($this->employee, 'employee')
        ->between('2026-11-03', '2026-11-05')
        ->deposit(100000, PaymentMethod::Cash)
        ->fixed()
        ->create(['people_count' => 4]);
    $reservation->extras()->attach(Extra::create(['name' => 'Cochera']));

    $grid = calendar('2026-11-01', '2026-11-30');
    $block = $grid->rows[0]->blocks[0];

    // Window starts on the morning of the 1st (column 1). The 3rd afternoon
    // is column 6; the 5th morning is column 9, so the block ends at line 10.
    expect($block->startColumn)->toBe(6)
        ->and($block->endColumn)->toBe(10)
        ->and($block->clipLeft)->toBeFalse()
        ->and($block->clipRight)->toBeFalse()
        ->and($block->color)->toBe('#4fc3f7')
        ->and($block->guestName)->toBe('Martín Medina')
        ->and($block->peopleLine)->toBe('4 pers. (+1) · Señado $100.000')
        ->and($block->employeeName)->toBe('Tomás')
        ->and($block->isMovable)->toBeFalse()
        ->and($block->extras)->toBe([['name' => 'Cochera', 'icon' => 'parking']])
        ->and($grid->slotCount())->toBe(60)
        ->and($grid->reservationCount)->toBe(1);
});

test('a reservation crossing the window edges is clipped on both sides', function () {
    Reservation::factory()->for($this->apartment)->for($this->employee, 'employee')
        ->between('2026-10-28', '2026-12-03')->create();

    $block = calendar('2026-11-01', '2026-11-30')->rows[0]->blocks[0];

    expect($block->startColumn)->toBe(1)
        ->and($block->endColumn)->toBe(61)
        ->and($block->clipLeft)->toBeTrue()
        ->and($block->clipRight)->toBeTrue();
});

test('a reservation crossing the end of the month shows up cut in both months', function () {
    Reservation::factory()->for($this->apartment)->for($this->employee, 'employee')
        ->between('2026-12-28', '2027-01-03')->create();

    $december = calendar('2026-12-01', '2026-12-31')->rows[0]->blocks[0];
    $january = calendar('2027-01-01', '2027-01-31')->rows[0]->blocks[0];

    expect($december->clipRight)->toBeTrue()->and($december->clipLeft)->toBeFalse()
        ->and($january->clipLeft)->toBeTrue()->and($january->clipRight)->toBeFalse()
        ->and($january->startColumn)->toBe(1)
        ->and($january->endColumn)->toBe(6); // until the morning of the 3rd
});

test('a checkout morning just before the window is not shown', function () {
    Reservation::factory()->for($this->apartment)->for($this->employee, 'employee')
        ->between('2026-10-28', '2026-10-31')->create();

    expect(calendar('2026-11-01', '2026-11-30')->rows[0]->blocks)->toBe([]);
});

test('cancelled reservations are not on the calendar', function () {
    Reservation::factory()->for($this->apartment)->for($this->employee, 'employee')
        ->between('2026-11-03', '2026-11-05')->create()->delete();

    expect(calendar('2026-11-01', '2026-11-30')->rows[0]->blocks)->toBe([]);
});

test('inactive apartments only show up when they have reservations in the window', function () {
    $inactive = Apartment::factory()->inactive()->create(['number' => '306', 'sort_order' => 2]);

    expect(collect(calendar('2026-11-01', '2026-11-30')->rows)->pluck('apartment.number')->all())->toBe(['105']);

    Reservation::factory()->for($inactive)->for($this->employee, 'employee')->between('2026-11-03', '2026-11-05')->create();

    $grid = calendar('2026-11-01', '2026-11-30');

    expect(collect($grid->rows)->pluck('apartment.number')->all())->toBe(['105', '306'])
        ->and($grid->activeApartments)->toBe(1);
});

test('free apartments are counted per night', function () {
    Apartment::factory()->create(['sort_order' => 2]);
    // Occupies the nights of the 3rd and 4th; leaves on the 5th morning.
    Reservation::factory()->for($this->apartment)->for($this->employee, 'employee')
        ->between('2026-11-03', '2026-11-05')->create();

    $free = calendar('2026-11-02', '2026-11-05')->freeByNight;

    expect($free)->toBe([2, 1, 1, 2]);
});

test('holidays and today are marked on the days', function () {
    Holiday::create(['date' => '2026-11-23', 'name' => 'Día de la Soberanía Nacional', 'type' => 'trasladable']);
    $this->travelTo(CarbonImmutable::parse('2026-11-25 10:00'));

    $days = collect(calendar('2026-11-01', '2026-11-30')->days);

    expect($days->firstWhere(fn ($day) => $day->date->day === 23)->holiday)->toBe('Día de la Soberanía Nacional')
        ->and($days->filter->isToday->first()->date->toDateString())->toBe('2026-11-25')
        ->and($days->first()->isWeekend)->toBeTrue(); // 1/11/2026 is a Sunday
});
