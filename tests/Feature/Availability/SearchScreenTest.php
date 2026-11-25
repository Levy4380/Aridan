<?php

use App\Livewire\Availability\Search;
use App\Models\Apartment;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 10:00'));
    $this->actingAs(User::factory()->create());
});

test('the search starts today for two nights', function () {
    Livewire::test(Search::class)
        ->assertSet('checkIn', '2026-11-25')
        ->assertSet('checkOut', '2026-11-27')
        ->assertSee(trans_choice('availability.nights', 2, ['count' => 2]));
});

test('results come grouped and the best option is marked', function () {
    Apartment::factory()->create(['number' => '102', 'capacity' => 3, 'sort_order' => 1]);
    Apartment::factory()->create(['number' => '104', 'capacity' => 2, 'sort_order' => 2]);
    $taken = Apartment::factory()->create(['number' => '205', 'capacity' => 4, 'sort_order' => 3]);
    Reservation::factory()->for($taken)
        ->for(Guest::factory()->state(['first_name' => 'Ana', 'last_name' => 'Gómez']))
        ->between('2026-12-01', '2026-12-05')->create();

    $this->get(route('availability', ['entrada' => '2026-12-02', 'salida' => '2026-12-04', 'personas' => 3]))
        ->assertOk()
        ->assertSee(__('availability.available', ['count' => 2]))
        ->assertSeeInOrder(['102', __('availability.fit.exact'), __('availability.best'), '104'])
        ->assertSee(__('availability.movable', ['count' => 1]))
        ->assertSee('Ana Gómez');
});

test('a checkout before the check-in shows an error instead of results', function () {
    Livewire::test(Search::class)
        ->set('checkIn', '2026-12-10')
        ->set('checkOut', '2026-12-05')
        ->assertSee(__('availability.invalid_dates'))
        ->assertDontSee(__('availability.best'));
});

test('an empty or zero people field waits instead of failing or searching', function () {
    Apartment::factory()->create(['number' => '102']);

    Livewire::test(Search::class)
        ->set('people', '')
        ->assertOk()
        ->assertSee(__('availability.people_prompt'))
        ->assertDontSee(__('availability.best'))
        ->set('people', '0')
        ->assertSee(__('availability.people_prompt'))
        ->set('people', '4')
        ->assertDontSee(__('availability.people_prompt'))
        ->assertSee(__('availability.available', ['count' => 1]));

    $this->get(route('availability', ['personas' => '']))->assertOk()->assertSee(__('availability.people_prompt'));
});

test('the whole row of the apartments and employees tables opens the drawer', function () {
    Apartment::factory()->create();

    // The edit button is stretched over its row: one click target, still a real button.
    $this->get(route('apartments.index'))->assertSee('relative cursor-pointer', escape: false)->assertSee('after:absolute after:inset-0', escape: false);
    $this->get(route('employees.index'))->assertSee('relative cursor-pointer', escape: false)->assertSee('after:absolute after:inset-0', escape: false);
});

test('moving the check-in past the checkout keeps the stay one night long', function () {
    Livewire::test(Search::class)
        ->set('checkOut', '2026-11-27')
        ->set('checkIn', '2026-11-30')
        ->assertSet('checkOut', '2026-12-01');
});

test('the calendar links to the search', function () {
    $this->get(route('calendar'))
        ->assertSee(route('availability'))
        ->assertSee(__('calendar.new_reservation'));
});
