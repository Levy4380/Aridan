<?php

namespace App\Livewire\Availability;

use App\Domain\StayRange;
use App\Queries\ApartmentSuggestions;
use App\Queries\Availability\AvailabilitySearch;
use App\Queries\AvailabilityQuery;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Availability search (/buscador): the only entry point for a new
 * reservation. Results recalculate as the fields change; everything lives in
 * the URL so the search can be shared and "Cambiar" can come back to it.
 */
class Search extends Component
{
    #[Url(as: 'entrada')]
    public string $checkIn = '';

    #[Url(as: 'salida')]
    public string $checkOut = '';

    /**
     * Kept as typed (a string): while the employee erases it to write another
     * number it can be empty, and that must not break nor search.
     */
    #[Url(as: 'personas')]
    public string $people = '2';

    #[Url(as: 'temprana')]
    public bool $earlyCheckIn = false;

    #[Url(as: 'tardia')]
    public bool $lateCheckOut = false;

    public function mount(): void
    {
        $this->checkIn = $this->validDate($this->checkIn) ?? CarbonImmutable::today()->toDateString();
        $this->checkOut = $this->validDate($this->checkOut)
            ?? CarbonImmutable::parse($this->checkIn)->addDays(2)->toDateString();
    }

    /**
     * Keep the stay at least one night long when the check-in moves past it.
     */
    public function updatedCheckIn(): void
    {
        $in = $this->validDate($this->checkIn);
        $out = $this->validDate($this->checkOut);

        if ($in && $out && $out <= $in) {
            $this->checkOut = CarbonImmutable::parse($in)->addDay()->toDateString();
        }
    }

    #[On('reservation-changed')]
    public function refresh(): void
    {
        // Re-render with fresh data.
    }

    public function render(ApartmentSuggestions $suggestions, AvailabilityQuery $availability): View
    {
        $stay = $this->stay();
        $people = $this->peopleCount();
        $searching = $stay && $people;

        return view('livewire.availability.search', [
            'stay' => $stay,
            'peopleCount' => $people,
            'suggestions' => $searching ? $suggestions(new AvailabilitySearch($stay, $people)) : null,
            'mapDays' => $searching ? $this->mapDays($availability, $stay) : [],
        ])->title(__('availability.title'));
    }

    /**
     * The number of people, or null while the field doesn't hold a number
     * greater than 0 (nothing is searched until it does).
     */
    public function peopleCount(): ?int
    {
        $people = trim($this->people);

        return ctype_digit($people) && (int) $people > 0 ? min((int) $people, 30) : null;
    }

    private function stay(): ?StayRange
    {
        $in = $this->validDate($this->checkIn);
        $out = $this->validDate($this->checkOut);

        if (! $in || ! $out || $out <= $in) {
            return null;
        }

        return StayRange::make($in, $out, $this->earlyCheckIn, $this->lateCheckOut);
    }

    /**
     * @return list<array{date: CarbonImmutable, requested: bool}>
     */
    private function mapDays(AvailabilityQuery $availability, StayRange $stay): array
    {
        [$from, $to] = $availability->mapWindow($stay);
        $days = [];

        for ($date = $from; $date->lessThanOrEqualTo($to); $date = $date->addDay()) {
            $slot = StayRange::slot($date, StayRange::MORNING);
            $days[] = [
                'date' => $date,
                'requested' => $slot + 1 >= $stay->startSlot && $slot < $stay->endSlot,
            ];
        }

        return $days;
    }

    private function validDate(string $value): ?string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value)->toDateString() === $value ? $value : null;
        } catch (Throwable) {
            return null;
        }
    }
}
