<?php

use App\Domain\CapacityFit;
use App\Domain\StayRange;
use App\Models\Apartment;
use App\Models\Reservation;
use App\Queries\ApartmentSuggestions;
use App\Queries\Availability\AvailabilitySearch;

function suggest(string $in, string $out, int $people)
{
    return app(ApartmentSuggestions::class)(new AvailabilitySearch(StayRange::make($in, $out), $people));
}

function numbers(array $items): array
{
    return collect($items)->map(fn ($item) => ($item->availability ?? $item)->apartment->number)->all();
}

test('exact capacity first, then bigger (less spare first), then smaller', function () {
    Apartment::factory()->create(['number' => 'chico', 'capacity' => 2, 'sort_order' => 1]);
    Apartment::factory()->create(['number' => 'grande', 'capacity' => 5, 'sort_order' => 2]);
    Apartment::factory()->create(['number' => 'justo', 'capacity' => 3, 'sort_order' => 3]);
    Apartment::factory()->create(['number' => 'sobra-uno', 'capacity' => 4, 'sort_order' => 4]);

    $suggestions = suggest('2026-11-10', '2026-11-12', 3);

    expect(numbers($suggestions->available))->toBe(['justo', 'sobra-uno', 'grande', 'chico'])
        ->and($suggestions->best()->availability->apartment->number)->toBe('justo')
        ->and($suggestions->best()->isBest())->toBeTrue()
        ->and($suggestions->available[3]->fit->kind)->toBe(CapacityFit::SMALLER)
        ->and($suggestions->available[3]->fit->difference)->toBe(1);
});

test('with the same fit, the apartment that leaves fewer loose days wins', function () {
    // CLAUDE.md's example: for a stay from the 10th to the 11th, an apartment
    // free only those days beats one free the whole week.
    $wholeWeek = Apartment::factory()->create(['number' => 'semana', 'capacity' => 2, 'sort_order' => 1]);
    $tight = Apartment::factory()->create(['number' => 'justo', 'capacity' => 2, 'sort_order' => 2]);

    Reservation::factory()->for($tight)->between('2026-11-07', '2026-11-10')->create();
    Reservation::factory()->for($tight)->between('2026-11-11', '2026-11-14')->create();
    Reservation::factory()->for($wholeWeek)->between('2026-11-01', '2026-11-04')->create();
    Reservation::factory()->for($wholeWeek)->between('2026-11-18', '2026-11-20')->create();

    expect(numbers(suggest('2026-11-10', '2026-11-11', 2)->available))->toBe(['justo', 'semana']);
});

test('ties are broken by the apartment order', function () {
    Apartment::factory()->create(['number' => 'segundo', 'capacity' => 2, 'sort_order' => 20]);
    Apartment::factory()->create(['number' => 'primero', 'capacity' => 2, 'sort_order' => 10]);

    expect(numbers(suggest('2026-11-10', '2026-11-12', 2)->available))->toBe(['primero', 'segundo']);
});

test('occupied apartments go to the movable or unavailable groups', function () {
    $free = Apartment::factory()->create(['number' => 'libre', 'sort_order' => 1]);
    $movable = Apartment::factory()->create(['number' => 'movible', 'sort_order' => 2]);
    $fixed = Apartment::factory()->create(['number' => 'fija', 'sort_order' => 3]);
    $mixed = Apartment::factory()->create(['number' => 'mixta', 'sort_order' => 4]);
    Apartment::factory()->inactive()->create(['number' => 'inactivo', 'sort_order' => 5]);

    Reservation::factory()->for($movable)->between('2026-11-09', '2026-11-11')->create();
    Reservation::factory()->for($fixed)->between('2026-11-09', '2026-11-11')->fixed()->create();
    Reservation::factory()->for($mixed)->between('2026-11-08', '2026-11-11')->create();
    Reservation::factory()->for($mixed)->between('2026-11-11', '2026-11-13')->fixed()->create();

    $suggestions = suggest('2026-11-10', '2026-11-12', 2);

    expect(numbers($suggestions->available))->toBe(['libre'])
        ->and(numbers($suggestions->movable))->toBe(['movible'])
        ->and(numbers($suggestions->unavailable))->toBe(['fija', 'mixta', 'inactivo']);
});

test('an inactive apartment is never suggested even when free', function () {
    Apartment::factory()->inactive()->create(['number' => 'inactivo']);

    $suggestions = suggest('2026-11-10', '2026-11-12', 2);

    expect($suggestions->available)->toBe([])
        ->and($suggestions->best())->toBeNull()
        ->and(numbers($suggestions->unavailable))->toBe(['inactivo']);
});
