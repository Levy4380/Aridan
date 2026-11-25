<?php

use App\Livewire\Availability\Search;
use App\Livewire\Reservations\Panel;
use App\Models\Apartment;
use App\Models\Extra;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 10:00'));
    $this->actingAs(User::factory()->create());
    $this->a205 = Apartment::factory()->create(['number' => '205', 'capacity' => 3, 'sort_order' => 1]);
    $this->a305 = Apartment::factory()->create(['number' => '305', 'capacity' => 4, 'sort_order' => 2]);
    $this->reservation = Reservation::factory()
        ->for($this->a205)
        ->for(Guest::factory()->state(['first_name' => 'Raúl', 'last_name' => 'Silva']))
        ->between('2026-12-08', '2026-12-12')
        ->deposit(100000)
        ->create(['people_count' => 3, 'notes' => 'Llegan tarde']);
    $this->reservation->extras()->attach(Extra::create(['name' => 'Cochera']));
});

test('edit mode starts with the reservation data and does not collide with itself', function () {
    Livewire::withQueryParams(['editar' => $this->reservation->id])
        ->test(Search::class)
        ->assertSet('checkIn', '2026-12-08')
        ->assertSet('checkOut', '2026-12-12')
        ->assertSet('people', '3')
        ->assertSee(__('availability.edit.title'))
        ->assertSee('Raúl Silva')
        ->assertSee(__('availability.edit.current'))
        // Its own apartment shows up as available: the reservation is ignored.
        ->assertSee(__('availability.available', ['count' => 2]))
        ->assertSee(__('availability.edit.use'))
        ->assertDontSee(__('availability.reserve'));
});

test('new dates are applied after a short confirmation, keeping everything else', function () {
    Livewire::withQueryParams(['editar' => $this->reservation->id])
        ->test(Search::class)
        ->set('checkOut', '2026-12-14')
        ->call('chooseForEditing', $this->a205->id)
        ->assertSee(__('availability.edit.apply'))
        ->call('applyChange')
        ->assertRedirect(route('calendar', ['desde' => '2026-12-01', 'reserva' => $this->reservation->id, 'resaltar' => $this->reservation->id]));

    expect($this->reservation->fresh())
        ->check_out_date->toDateString()->toBe('2026-12-14')
        ->apartment_id->toBe($this->a205->id)
        ->notes->toBe('Llegan tarde')
        ->paidAmount()->toBe(100000.0)
        ->and($this->reservation->fresh()->extras->pluck('name')->all())->toBe(['Cochera']);
});

test('people and apartment can change too; a non-movable one is warned, not blocked', function () {
    $this->reservation->update(['is_movable' => false]);

    Livewire::withQueryParams(['editar' => $this->reservation->id])
        ->test(Search::class)
        ->set('people', '4')
        ->call('chooseForEditing', $this->a305->id)
        ->assertSee(__('warnings.not_movable'))
        ->assertSee(__('reservations.create.save_anyway'))
        ->call('applyChange');

    expect($this->reservation->fresh())
        ->apartment_id->toBe($this->a305->id)
        ->people_count->toBe(4);
});

test('on reload, what is already in the URL wins over the reservation data', function () {
    Livewire::withQueryParams(['editar' => $this->reservation->id, 'personas' => '2', 'salida' => '2026-12-15'])
        ->test(Search::class)
        ->assertSet('people', '2')
        ->assertSet('checkOut', '2026-12-15')
        ->assertSet('checkIn', '2026-12-08');
});

test('changing the search drops a pending confirmation', function () {
    Livewire::withQueryParams(['editar' => $this->reservation->id])
        ->test(Search::class)
        ->call('chooseForEditing', $this->a305->id)
        ->assertSet('confirmApartmentId', $this->a305->id)
        ->set('people', '2')
        ->assertSet('confirmApartmentId', null);
});

test('a taken apartment is not offered and the action still blocks overlaps', function () {
    Reservation::factory()->for($this->a305)->between('2026-12-13', '2026-12-15')->create();

    Livewire::withQueryParams(['editar' => $this->reservation->id])
        ->test(Search::class)
        ->set('checkOut', '2026-12-14')
        ->assertDontSeeHtml('data-test="use-305"')
        ->call('chooseForEditing', $this->a305->id) // bypassing the UI
        ->call('applyChange')
        ->assertHasErrors(['reservation']);

    expect($this->reservation->fresh()->apartment_id)->toBe($this->a205->id);
});

test('the panel "Cambiar" goes to the search in edit mode', function () {
    Livewire::test(Panel::class)
        ->call('open', $this->reservation->id)
        ->call('startEdit')
        ->assertSee(route('availability', ['editar' => $this->reservation->id]));
});

test('an unknown or cancelled reservation falls back to a normal search', function () {
    $this->reservation->delete();

    Livewire::withQueryParams(['editar' => $this->reservation->id])
        ->test(Search::class)
        ->assertSet('editingId', null)
        ->assertSee(__('availability.title'));
});
