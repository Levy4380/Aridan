<?php

use App\Actions\Reservations\UpdateReservation;
use App\Livewire\Reservations\CreateForm;
use App\Livewire\Reservations\Panel;
use App\Models\Apartment;
use App\Models\Extra;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use App\Queries\CalendarQuery;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

/*
| Reservations don't include parking or breakfast for now: the extras flow
| is off (config/extras.php) but stays in the code, ready to turn on.
*/

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 10:00'));
    $this->actingAs(User::factory()->create());
    $this->parking = Extra::create(['name' => 'Cochera']);
    $this->apartment = Apartment::factory()->create(['number' => '205', 'capacity' => 3]);
    $this->reservation = Reservation::factory()->for($this->apartment)->between('2026-12-10', '2026-12-12')->create();
    $this->reservation->extras()->attach($this->parking);
});

test('extras are off by default', function () {
    expect(Extra::enabled())->toBeFalse();
});

test('the form, the detail and the calendar do not show them', function () {
    Livewire::test(CreateForm::class)
        ->call('open', '2026-12-20', '2026-12-22', false, false, 2, $this->apartment->id)
        ->assertDontSee('Cochera');

    Livewire::test(Panel::class)
        ->call('open', $this->reservation->id)
        ->assertDontSee(__('reservations.panel.extras'))
        ->assertDontSee('Cochera');

    $block = app(CalendarQuery::class)(CarbonImmutable::parse('2026-12-01'), CarbonImmutable::parse('2026-12-31'))
        ->rows[0]->blocks[0];

    expect($block->extras)->toBe([]);

    $this->get(route('calendar', ['desde' => '2026-12-01']))
        ->assertOk()
        ->assertDontSee(__('calendar.legend_parking'));
});

test('saving a reservation leaves the extras it already had untouched', function () {
    Livewire::test(Panel::class)
        ->call('open', $this->reservation->id)
        ->call('startEdit')
        ->set('payment_status', 'pending')
        ->set('notes', 'Sin cambios en extras')
        ->call('save')
        ->assertHasNoErrors();

    app(UpdateReservation::class)($this->reservation->fresh(), [
        'apartment_id' => $this->apartment->id,
        'employee_id' => $this->reservation->employee_id,
        'check_in_date' => '2026-12-10',
        'check_out_date' => '2026-12-12',
        'people_count' => 2,
        'payment_status' => 'pending',
        'extra_ids' => [],
        'guest_id' => $this->reservation->guest_id,
    ]);

    expect($this->reservation->fresh()->extras->pluck('name')->all())->toBe(['Cochera']);
});

test('turning them on brings the toggles back', function () {
    config(['extras.enabled' => true]);

    Livewire::test(CreateForm::class)
        ->call('open', '2026-12-20', '2026-12-22', false, false, 2, $this->apartment->id, Guest::factory()->create()->id)
        ->assertSee('Cochera');
});
