<?php

use App\Livewire\Reservations\CreateForm;
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
    $this->apartment = Apartment::factory()->create(['number' => '205', 'capacity' => 3, 'sort_order' => 1]);
    $this->guest = Guest::factory()->create(['first_name' => 'Elena', 'last_name' => 'Romero', 'phone' => '11 5555-0142']);
});

function openForm(array $overrides = [])
{
    return Livewire::test(CreateForm::class)->call('open', ...[
        'checkIn' => '2026-12-10',
        'checkOut' => '2026-12-12',
        'early' => false,
        'late' => false,
        'people' => 3,
        'apartmentId' => test()->apartment->id,
        ...$overrides,
    ]);
}

test('the modal opens with the search data fixed and the logged-in employee as responsible', function () {
    openForm()
        ->assertSet('show', true)
        ->assertSet('employee_id', $this->me->id)
        ->assertSet('payment_status', 'deposit')
        ->assertSee(__('reservations.create.title'))
        ->assertSee('205');
});

test('it suggests three apartments with the chosen one included', function () {
    foreach (range(2, 5) as $order) {
        Apartment::factory()->create(['capacity' => 3, 'sort_order' => $order]);
    }
    $last = Apartment::factory()->create(['number' => 'ultimo', 'capacity' => 6, 'sort_order' => 9]);

    $options = openForm(['apartmentId' => $last->id])->get('options');

    expect($options)->toHaveCount(3)
        ->and(collect($options)->pluck('availability.apartment.number')->last())->toBe('ultimo');
});

test('an existing guest is found and the reservation saved, landing on the calendar', function () {
    openForm()
        ->set('guestSearch', '011 15 5555-0142')
        ->assertSee('Elena Romero')
        ->call('selectGuest', $this->guest->id)
        ->set('amount', '$ 100.000')
        ->set('payment_method', 'cash')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('calendar', ['desde' => '2026-12-01', 'resaltar' => Reservation::sole()->id]));

    expect(Reservation::sole())
        ->amount->toBe('100000.00')
        ->guest_id->toBe($this->guest->id)
        ->employee_id->toBe($this->me->id);
});

test('a guest is required', function () {
    openForm()->call('save')->assertHasErrors(['guest_id']);
});

test('warnings need one confirmation: "Guardar igual"', function () {
    $form = openForm(['people' => 4])
        ->call('selectGuest', $this->guest->id)
        ->set('payment_status', 'pending')
        ->assertSee(trans_choice('warnings.capacity_exceeded', 1, ['number' => '205', 'capacity' => 3, 'people' => 4, 'count' => 1]))
        ->call('save')
        ->assertNoRedirect()
        ->assertSee(__('reservations.create.save_anyway'));

    expect(Reservation::count())->toBe(0);

    $form->call('save')->assertRedirect();

    expect(Reservation::sole()->people_count)->toBe(4);
});

test('adding the missing mattress removes the capacity warning', function () {
    openForm(['people' => 4])
        ->call('selectGuest', $this->guest->id)
        ->set('payment_status', 'pending')
        ->call('addMattress')
        ->call('save')
        ->assertRedirect();

    expect(Reservation::sole()->extra_mattresses)->toBe(1);
});

test('a new guest can be loaded in the same modal', function () {
    openForm()
        ->set('guestSearch', '351 555 0142')
        ->call('startNewGuest')
        ->assertSet('guest.phone', '351 555 0142')
        ->set('guest.first_name', 'Lucía')
        ->set('guest.last_name', 'López')
        ->set('payment_status', 'pending')
        ->call('save')
        ->assertRedirect();

    expect(Reservation::sole()->guest->full_name)->toBe('Lucía López');
});

test('loading a phone that already exists offers to use that guest', function () {
    openForm()
        ->call('startNewGuest')
        ->set('guest.phone', '+54 9 11 5555 0142')
        ->assertSee(__('guests.picker.phone_owner', ['name' => 'Elena Romero']))
        ->call('selectGuest', $this->guest->id)
        ->assertSet('guest_id', $this->guest->id)
        ->assertSet('creatingGuest', false);
});

test('the responsible can be another active employee', function () {
    $rodrigo = User::factory()->create(['name' => 'Rodrigo']);

    openForm()
        ->call('selectGuest', $this->guest->id)
        ->set('employee_id', $rodrigo->id)
        ->set('payment_status', 'pending')
        ->call('save');

    expect(Reservation::sole()->employee_id)->toBe($rodrigo->id);
});

test('if someone else booked it meanwhile the overlap is shown in the modal', function () {
    openForm()->call('selectGuest', $this->guest->id)->set('payment_status', 'pending')->tap(function () {
        Reservation::factory()->for($this->apartment)->between('2026-12-09', '2026-12-11')->create();
    })->call('save')
        ->assertHasErrors(['reservation'])
        ->assertNoRedirect();
});
