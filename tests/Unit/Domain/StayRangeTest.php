<?php

use App\Domain\StayRange;

test('a normal stay goes from the check-in afternoon to the check-out morning', function () {
    $stay = StayRange::make('2026-11-10', '2026-11-12');
    $day10 = StayRange::dayNumber('2026-11-10');

    expect($stay->startSlot)->toBe($day10 * 2 + StayRange::AFTERNOON)
        ->and($stay->endSlot)->toBe(($day10 + 2) * 2 + StayRange::MORNING + 1)
        ->and($stay->nights())->toBe(2);
});

test('early check-in takes the morning and late check-out the afternoon', function () {
    $normal = StayRange::make('2026-11-10', '2026-11-12');
    $early = StayRange::make('2026-11-10', '2026-11-12', earlyCheckIn: true);
    $late = StayRange::make('2026-11-10', '2026-11-12', lateCheckOut: true);

    expect($early->startSlot)->toBe($normal->startSlot - 1)
        ->and($early->endSlot)->toBe($normal->endSlot)
        ->and($late->startSlot)->toBe($normal->startSlot)
        ->and($late->endSlot)->toBe($normal->endSlot + 1);
});

/*
 * The overlap table from arquitectura-backend.md (section 4): A leaves on
 * day D, B arrives on day D.
 */
test('same-day turnover', function (bool $earlyB, bool $lateA, bool $overlaps) {
    $a = StayRange::make('2026-11-08', '2026-11-10', lateCheckOut: $lateA);
    $b = StayRange::make('2026-11-10', '2026-11-12', earlyCheckIn: $earlyB);

    expect($a->overlaps($b))->toBe($overlaps)
        ->and($b->overlaps($a))->toBe($overlaps);
})->with([
    'morning checkout + afternoon check-in coexist' => [false, false, false],
    'B with early check-in collides' => [true, false, true],
    'A with late check-out collides' => [false, true, true],
    'both collide' => [true, true, true],
]);

test('overlap cases', function (array $a, array $b, bool $overlaps) {
    expect(StayRange::make(...$a)->overlaps(StayRange::make(...$b)))->toBe($overlaps);
})->with([
    'same dates' => [['2026-11-10', '2026-11-12'], ['2026-11-10', '2026-11-12'], true],
    'one inside the other' => [['2026-11-01', '2026-11-30'], ['2026-11-10', '2026-11-12'], true],
    'partial overlap' => [['2026-11-10', '2026-11-14'], ['2026-11-12', '2026-11-16'], true],
    'a free day between' => [['2026-11-10', '2026-11-12'], ['2026-11-13', '2026-11-15'], false],
    'late check-out with a free day after' => [['2026-11-10', '2026-11-12', false, true], ['2026-11-13', '2026-11-15'], false],
    'early check-in after a free day' => [['2026-11-10', '2026-11-12'], ['2026-11-13', '2026-11-15', true], false],
]);

test('a stay can cross months and years without special handling', function () {
    $stay = StayRange::make('2026-12-28', '2027-01-03');

    expect($stay->nights())->toBe(6)
        ->and($stay->overlaps(StayRange::make('2027-01-01', '2027-01-02')))->toBeTrue()
        ->and($stay->overlaps(StayRange::make('2027-01-03', '2027-01-05')))->toBeFalse();
});

test('slots map back to their date', function () {
    $stay = StayRange::make('2026-11-10', '2026-11-12');

    expect(StayRange::dateOfSlot($stay->startSlot)->toDateString())->toBe('2026-11-10')
        ->and(StayRange::dateOfSlot($stay->endSlot - 1)->toDateString())->toBe('2026-11-12');
});

test('slots ignore the time of day and the timezone', function () {
    $buenosAires = new DateTimeImmutable('2026-11-10 23:30', new DateTimeZone('America/Argentina/Buenos_Aires'));

    expect(StayRange::dayNumber($buenosAires))->toBe(StayRange::dayNumber('2026-11-10'));
});

test('check-out must be after check-in', function () {
    StayRange::make('2026-11-10', '2026-11-10');
})->throws(InvalidArgumentException::class);
