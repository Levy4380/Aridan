<?php

namespace App\Queries;

use App\Domain\CapacityFit;
use App\Queries\Availability\ApartmentAvailability;
use App\Queries\Availability\AvailabilitySearch;
use App\Queries\Availability\SuggestedApartment;
use App\Queries\Availability\Suggestions;

/**
 * Orders the availability into the three groups of the search. Kept in its
 * own class so the weights can be tuned without touching anything else.
 *
 * Available apartments, best first:
 *   1. Capacity fit: exact, then bigger (less spare first), then smaller.
 *   2. Fragmentation: fewer loose free days left before and after.
 *   3. Tiebreak: apartment order.
 */
class ApartmentSuggestions
{
    public function __construct(private AvailabilityQuery $availability) {}

    public function __invoke(AvailabilitySearch $search): Suggestions
    {
        $all = ($this->availability)($search);

        $available = $all
            ->filter(fn (ApartmentAvailability $a) => $a->apartment->is_active && $a->isFree())
            ->map(fn (ApartmentAvailability $a) => [
                'availability' => $a,
                'fit' => new CapacityFit($a->apartment->capacity, $search->people),
            ])
            ->sort(fn (array $x, array $y) => [...$x['fit']->rank(), $x['availability']->fragmentation(), $x['availability']->apartment->sort_order, $x['availability']->apartment->number]
                <=> [...$y['fit']->rank(), $y['availability']->fragmentation(), $y['availability']->apartment->sort_order, $y['availability']->apartment->number])
            ->values()
            ->map(fn (array $item, int $index) => new SuggestedApartment($item['availability'], $item['fit'], $index + 1))
            ->all();

        $movable = $all
            ->filter(fn (ApartmentAvailability $a) => $a->apartment->is_active && $a->isFreeableByMoving())
            ->values()
            ->all();

        $unavailable = $all
            ->filter(fn (ApartmentAvailability $a) => ! $a->apartment->is_active || (! $a->isFree() && ! $a->isFreeableByMoving()))
            ->values()
            ->all();

        return new Suggestions($available, $movable, $unavailable);
    }
}
