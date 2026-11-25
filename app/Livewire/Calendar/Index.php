<?php

namespace App\Livewire\Calendar;

use App\Queries\CalendarQuery;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Calendar screen (/calendario): rows = apartments, columns = days split in
 * morning/afternoon. The component only picks the window; CalendarQuery
 * builds everything. A window is just a date range: no month logic.
 */
class Index extends Component
{
    public const RANGE_MONTH = 'mes';

    public const RANGE_TWO_WEEKS = '2semanas';

    #[Url(as: 'desde')]
    public string $from = '';

    #[Url(as: 'rango')]
    public string $range = self::RANGE_MONTH;

    /** A reservation just saved: its block is highlighted for a moment. */
    #[Url(as: 'resaltar')]
    public ?int $highlight = null;

    public function mount(): void
    {
        if (! in_array($this->range, [self::RANGE_MONTH, self::RANGE_TWO_WEEKS], true)) {
            $this->range = self::RANGE_MONTH;
        }

        $this->from = $this->normalizedStart($this->parsedFrom())->toDateString();
    }

    public function updatedRange(): void
    {
        $from = $this->parsedFrom();
        $today = CarbonImmutable::today();

        // Switching to two weeks while looking at the current month shows
        // the current week, which is what people usually want to see.
        if ($this->range === self::RANGE_TWO_WEEKS && $from->isSameMonth($today)) {
            $from = $today;
        }

        $this->mount();
        $this->from = $this->normalizedStart($from)->toDateString();
    }

    public function previous(): void
    {
        $start = $this->parsedFrom();
        $this->from = ($this->range === self::RANGE_MONTH ? $start->subMonthNoOverflow() : $start->subDays(14))->toDateString();
    }

    public function next(): void
    {
        $start = $this->parsedFrom();
        $this->from = ($this->range === self::RANGE_MONTH ? $start->addMonthNoOverflow() : $start->addDays(14))->toDateString();
    }

    public function today(): void
    {
        $this->from = $this->normalizedStart(CarbonImmutable::today())->toDateString();
    }

    #[On('reservation-changed')]
    public function refresh(): void
    {
        // Re-render with fresh data.
    }

    public function render(CalendarQuery $calendar): View
    {
        $from = $this->parsedFrom();
        $to = $this->range === self::RANGE_MONTH ? $from->endOfMonth()->startOfDay() : $from->addDays(13);

        return view('livewire.calendar.index', [
            'grid' => $calendar($from, $to),
            'title' => $this->range === self::RANGE_MONTH
                ? ucfirst($from->locale('es')->isoFormat('MMMM YYYY'))
                : $from->format('d/m').' – '.$to->format('d/m/Y'),
        ])->title(__('calendar.title'));
    }

    private function parsedFrom(): CarbonImmutable
    {
        try {
            return $this->from !== '' ? CarbonImmutable::parse($this->from)->startOfDay() : CarbonImmutable::today();
        } catch (Throwable) {
            return CarbonImmutable::today();
        }
    }

    private function normalizedStart(CarbonImmutable $date): CarbonImmutable
    {
        return $this->range === self::RANGE_MONTH ? $date->startOfMonth() : $date->startOfWeek();
    }
}
