<?php

use App\Livewire\Calendar\Index;
use App\Models\Apartment;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 10:00'));
    $this->employee = User::factory()->create(['name' => 'Rodrigo', 'color' => '#81c784']);
    $this->actingAs($this->employee);
});

test('the calendar shows the current month with its reservations', function () {
    $apartment = Apartment::factory()->create(['number' => '205']);
    Reservation::factory()
        ->for($apartment)->for($this->employee, 'employee')
        ->for(Guest::factory()->state(['first_name' => 'Elena', 'last_name' => 'Romero']))
        ->between('2026-11-22', '2026-11-30')
        ->create();

    $this->get(route('calendar'))
        ->assertOk()
        ->assertSee('Noviembre 2026')
        ->assertSee('205')
        ->assertSee('Elena Romero')
        ->assertSee('background-color: #81c784', escape: false)
        ->assertSee(__('calendar.free_per_night'));
});

test('the window moves month by month and back to today', function () {
    Livewire::test(Index::class)
        ->assertSet('from', '2026-11-01')
        ->call('next')
        ->assertSet('from', '2026-12-01')
        ->assertSee('Diciembre 2026')
        ->call('previous')
        ->call('previous')
        ->assertSet('from', '2026-10-01')
        ->call('today')
        ->assertSet('from', '2026-11-01');
});

test('the two-week window starts on the current week', function () {
    Livewire::test(Index::class)
        ->set('range', Index::RANGE_TWO_WEEKS)
        ->assertSet('from', '2026-11-23')
        ->assertSee('23/11 – 06/12/2026')
        ->call('next')
        ->assertSet('from', '2026-12-07');
});

test('"Imprimir" prints the whole month, switching from the two-week view', function () {
    Livewire::test(Index::class)
        ->set('range', Index::RANGE_TWO_WEEKS)
        ->assertSet('from', '2026-11-23')
        ->call('printMonth')
        ->assertSet('range', Index::RANGE_MONTH)
        ->assertSet('from', '2026-11-01');
});

test('one button searches and books, next to "Imprimir"; only the grid is printed', function () {
    $this->get(route('calendar'))
        ->assertSeeInOrder([__('calendar.print'), __('calendar.search_and_book')])
        ->assertSeeHtml('data-test="print-button"')
        ->assertSeeHtml('data-test="search-and-book"')
        // App header, toolbar and legend are hidden when printing.
        ->assertSeeHtml('print:hidden');
});

test('the calendar uses the whole width; other screens keep a max width', function () {
    $this->get(route('calendar'))->assertDontSeeHtml('max-w-[1440px]');
    $this->get(route('guests.index'))->assertSeeHtml('max-w-[1440px]');
});

test('the window comes from the URL and bad values fall back to today', function () {
    Livewire::withQueryParams(['desde' => '2027-02-14', 'rango' => 'mes'])
        ->test(Index::class)
        ->assertSet('from', '2027-02-01');

    Livewire::withQueryParams(['desde' => 'cualquier-cosa', 'rango' => 'año'])
        ->test(Index::class)
        ->assertSet('range', Index::RANGE_MONTH)
        ->assertSet('from', '2026-11-01');
});
