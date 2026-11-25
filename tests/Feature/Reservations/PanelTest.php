<?php

use App\Livewire\Reservations\Panel;
use App\Models\Apartment;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 10:00'));
    $this->me = User::factory()->create(['name' => 'Tomás']);
    $this->actingAs($this->me);
    $this->apartment = Apartment::factory()->create(['number' => '205', 'capacity' => 4, 'sort_order' => 1]);
    $this->reservation = Reservation::factory()
        ->for($this->apartment)
        ->for(Guest::factory()->state(['first_name' => 'Raúl', 'last_name' => 'Silva']))
        ->for(User::factory()->state(['name' => 'Rodrigo']), 'employee')
        ->between('2026-12-08', '2026-12-12')
        ->paid(200000)
        ->create(['people_count' => 3]);
});

test('the panel shows the reservation detail', function () {
    Livewire::test(Panel::class)
        ->call('open', $this->reservation->id)
        ->assertSet('show', true)
        ->assertSee('Raúl Silva')
        ->assertSee('Depto 205')
        ->assertSee('Rodrigo')
        ->assertSee('$200.000');
});

test('a direct link opens the calendar on that month with the panel open', function () {
    $this->get(route('reservations.show', $this->reservation))
        ->assertRedirect(route('calendar', ['desde' => '2026-12-01', 'reserva' => $this->reservation->id]));

    $this->get(route('calendar', ['desde' => '2026-12-01', 'reserva' => $this->reservation->id]))
        ->assertOk()
        ->assertSee('Raúl Silva');
});

test('clicking a calendar block opens its panel', function () {
    $this->get(route('calendar', ['desde' => '2026-12-01']))
        ->assertSee("open-reservation', { id: {$this->reservation->id} }", escape: false);
});

test('editing saves what does not change the occupancy', function () {
    Livewire::test(Panel::class)
        ->call('open', $this->reservation->id)
        ->call('startEdit')
        ->assertSet('mode', Panel::EDIT)
        ->assertSet('payment_status', 'paid')
        ->set('notes', 'Llegan a las 22')
        ->set('employee_id', $this->me->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('mode', Panel::VIEW)
        ->assertDispatched('reservation-changed');

    expect($this->reservation->fresh())
        ->notes->toBe('Llegan a las 22')
        ->employee_id->toBe($this->me->id)
        ->paidAmount()->toBe(200000.0);
});

test('editing loads the payments and can add the rest in cash', function () {
    $reservation = Reservation::factory()->for($this->apartment)->between('2026-12-20', '2026-12-22')
        ->deposit(80000, total: 200000)->create();

    $panel = Livewire::test(Panel::class)
        ->call('open', $reservation->id)
        ->assertSee(__('reservations.panel.paid_of', ['paid' => '$80.000', 'total' => '$200.000']))
        ->assertSee(__('reservations.create.balance', ['amount' => '$120.000']))
        ->call('startEdit')
        ->assertSet('total_amount', '$200.000')
        ->assertSet('payments.0.amount', '$80.000')
        ->assertSet('payments.0.method', 'transfer')
        ->set('payment_status', 'paid')
        ->call('addPayment')
        ->set('payments.1.amount', '120.000')
        ->set('payments.1.method', 'cash')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('mode', Panel::VIEW);

    expect($reservation->fresh())
        ->payment_status->value->toBe('paid')
        ->paidAmount()->toBe(200000.0)
        ->and($reservation->fresh()->payments->pluck('method')->map->value->all())->toBe(['transfer', 'cash']);
});

test('moving lists free apartments and moves it', function () {
    $free = Apartment::factory()->create(['number' => '305', 'capacity' => 3, 'sort_order' => 2]);
    $taken = Apartment::factory()->create(['number' => '306', 'sort_order' => 3]);
    Reservation::factory()->for($taken)->between('2026-12-09', '2026-12-10')->create();

    Livewire::test(Panel::class)
        ->call('open', $this->reservation->id)
        ->call('startMove')
        ->assertSee('305')
        ->assertDontSee('306')
        ->set('moveTo', $free->id)
        ->call('move')
        ->assertHasNoErrors()
        ->assertSee(__('reservations.panel.moved', ['to' => '305', 'from' => '205', 'dates' => '08/12 al 12/12']));

    expect($this->reservation->fresh()->apartment_id)->toBe($free->id);
});

test('moving a non-movable reservation needs "Moverla igual"', function () {
    $this->reservation->update(['is_movable' => false]);
    $free = Apartment::factory()->create(['sort_order' => 2]);

    $panel = Livewire::test(Panel::class)
        ->call('open', $this->reservation->id)
        ->call('startMove')
        ->set('moveTo', $free->id)
        ->call('move')
        ->assertHasErrors(['moveAnyway']);

    expect($this->reservation->fresh()->apartment_id)->toBe($this->apartment->id);

    $panel->set('moveAnyway', true)->call('move')->assertHasNoErrors();

    expect($this->reservation->fresh()->apartment_id)->toBe($free->id);
});

test('cancelling asks first, then keeps it for control', function () {
    Livewire::test(Panel::class)
        ->call('open', $this->reservation->id)
        ->call('startCancel')
        ->assertSee(__('reservations.panel.cancel_question'))
        ->call('cancel')
        ->assertSee(__('reservations.panel.cancelled_heading'))
        ->assertSee('Tomás')
        ->assertDispatched('reservation-changed');

    expect(Reservation::withTrashed()->find($this->reservation->id))
        ->trashed()->toBeTrue()
        ->cancelled_by_id->toBe($this->me->id);
});

test('closing the panel clears the URL', function () {
    Livewire::test(Panel::class)
        ->call('open', $this->reservation->id)
        ->set('show', false)
        ->assertSet('reservationId', null);
});
