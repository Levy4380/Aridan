<?php

use App\Domain\StayRange;
use App\Models\Apartment;
use App\Models\Reservation;
use Illuminate\Database\QueryException;

/*
 * The exclusion constraint is the database safety net: even if code skipped
 * the validation, two active reservations can't share a half-day.
 */

beforeEach(function () {
    $this->apartment = Apartment::factory()->create();
});

test('the model derives the slots from dates and flags', function () {
    $reservation = Reservation::factory()->for($this->apartment)->between('2026-11-10', '2026-11-12')->create([
        'early_check_in' => true,
    ]);

    $stay = StayRange::make('2026-11-10', '2026-11-12', earlyCheckIn: true);

    expect($reservation->start_slot)->toBe($stay->startSlot)
        ->and($reservation->end_slot)->toBe($stay->endSlot);

    $reservation->update(['late_check_out' => true]);

    expect($reservation->fresh()->end_slot)->toBe($stay->endSlot + 1);
});

test('the database rejects two overlapping reservations in the same apartment', function () {
    Reservation::factory()->for($this->apartment)->between('2026-11-10', '2026-11-14')->create();

    Reservation::factory()->for($this->apartment)->between('2026-11-12', '2026-11-16')->create();
})->throws(QueryException::class, 'reservations_no_overlap');

test('a morning checkout and an afternoon check-in coexist', function () {
    Reservation::factory()->for($this->apartment)->between('2026-11-10', '2026-11-12')->create();
    Reservation::factory()->for($this->apartment)->between('2026-11-12', '2026-11-14')->create();

    expect($this->apartment->reservations()->count())->toBe(2);
});

test('an early check-in on a checkout morning is rejected', function () {
    Reservation::factory()->for($this->apartment)->between('2026-11-10', '2026-11-12')->create();

    Reservation::factory()->for($this->apartment)->between('2026-11-12', '2026-11-14')->create(['early_check_in' => true]);
})->throws(QueryException::class, 'reservations_no_overlap');

test('different apartments never collide', function () {
    Reservation::factory()->for($this->apartment)->between('2026-11-10', '2026-11-14')->create();
    Reservation::factory()->for(Apartment::factory())->between('2026-11-10', '2026-11-14')->create();

    expect(Reservation::count())->toBe(2);
});

test('a cancelled reservation no longer occupies the apartment', function () {
    $cancelled = Reservation::factory()->for($this->apartment)->between('2026-11-10', '2026-11-14')->create();
    $cancelled->delete();

    Reservation::factory()->for($this->apartment)->between('2026-11-10', '2026-11-14')->create();

    expect(Reservation::count())->toBe(1)
        ->and(Reservation::withTrashed()->count())->toBe(2);
});
