<?php

use App\Actions\Guests\SaveGuest;
use App\Livewire\Availability\Search;
use App\Livewire\Guests\Drawer;
use App\Livewire\Guests\Index;
use App\Livewire\Reservations\CreateForm;
use App\Models\Apartment;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 10:00'));
    $this->actingAs(User::factory()->create());
    $this->elena = Guest::factory()->create(['first_name' => 'Elena', 'last_name' => 'Romero', 'phone' => '11 5555-0142', 'document_number' => '30123456']);
    $this->raul = Guest::factory()->create(['first_name' => 'Raúl', 'last_name' => 'Silva', 'phone' => '351 555-0142', 'document_number' => null]);
});

test('the guests screen lists guests with their reservations', function () {
    Reservation::factory()->for($this->elena)->between('2026-11-01', '2026-11-05')->create();
    Reservation::factory()->for($this->elena)->between('2026-12-10', '2026-12-12')->create();
    Reservation::factory()->for($this->elena)->between('2026-10-01', '2026-10-05')->create()->delete();

    $this->get(route('guests.index'))
        ->assertOk()
        ->assertSee('Elena Romero')
        ->assertSee('11 5555-0142')
        ->assertSee('30.123.456')
        ->assertSee(__('guests.no_document'))
        ->assertSee('01/11/2026') // last stay already started, not the future one
        ->assertDontSee('10/12/2026')
        ->assertSee(__('nav.guests'));
});

test('the search finds guests by name, phone or document however they are typed', function (string $term, array $expected) {
    $names = Livewire::test(Index::class)->set('search', $term)->get('guests')->pluck('full_name')->all();

    expect($names)->toBe($expected);
})->with([
    'part of the name' => ['elena', ['Elena Romero']],
    'first and last name' => ['raul silva', []], // accents are kept as typed
    'full name with accent' => ['raúl silva', ['Raúl Silva']],
    'part of a phone' => ['555-0142', ['Elena Romero', 'Raúl Silva']],
    'phone written differently' => ['+54 9 11 5555-0142', ['Elena Romero']],
    'document with dots' => ['30.123.456', ['Elena Romero']],
]);

test('a guest can be created and edited from the drawer', function () {
    Livewire::test(Drawer::class)
        ->call('open')
        ->set('form.first_name', 'Lucía')
        ->set('form.last_name', 'López')
        ->set('form.phone', '0221 15 555-0101')
        ->call('save')
        ->assertHasNoErrors();

    $lucia = Guest::where('first_name', 'Lucía')->sole();
    expect($lucia->phone)->toBe('5492215550101');

    Livewire::test(Drawer::class)
        ->call('open', $lucia->id)
        ->set('form.document_number', '40.111.222')
        ->call('save')
        ->assertHasNoErrors();

    expect($lucia->fresh()->document_number)->toBe('40111222');
});

test('a repeated phone or document says who has it', function () {
    Livewire::test(Drawer::class)
        ->call('open')
        ->set('form.first_name', 'Otra')
        ->set('form.last_name', 'Persona')
        ->set('form.phone', '+54 9 11 5555 0142')
        ->call('save')
        ->assertHasErrors(['form.phone'])
        ->assertSee(__('reservations.errors.phone_taken', ['name' => 'Elena Romero']));

    expect(fn () => app(SaveGuest::class)(['first_name' => 'X', 'last_name' => 'Y', 'phone' => '11 4444-0000', 'document_number' => '30.123.456']))
        ->toThrow(ValidationException::class);
});

test('a guest keeps their own phone when edited', function () {
    app(SaveGuest::class)(['first_name' => 'Elena', 'last_name' => 'Romero de Paz', 'phone' => '011 15 5555-0142', 'document_number' => '30123456'], $this->elena);

    expect($this->elena->fresh()->last_name)->toBe('Romero de Paz');
});

test('the drawer shows the whole history, cancelled ones included', function () {
    $apartment = Apartment::factory()->create(['number' => '202']);
    Reservation::factory()->for($this->elena)->for($apartment)->between('2026-12-10', '2026-12-14')->deposit(100000)->create(['people_count' => 3]);
    Reservation::factory()->for($this->elena)->for($apartment)->between('2026-11-20', '2026-11-27')->create();
    Reservation::factory()->for($this->elena)->for($apartment)->between('2026-10-01', '2026-10-05')->create();
    Reservation::factory()->for($this->elena)->for($apartment)->between('2026-09-01', '2026-09-05')->create()->delete();

    Livewire::withQueryParams(['cliente' => $this->elena->id])
        ->test(Drawer::class)
        ->assertSet('show', true)
        ->assertSee('Depto 202 · 3 pers.')
        ->assertSee('Señado $100.000')
        ->assertSeeInOrder([
            __('reservations.timing.upcoming'),
            __('reservations.timing.current'),
            __('reservations.timing.past'),
            __('reservations.timing.cancelled'),
        ])
        ->assertSee(route('availability', ['cliente' => $this->elena->id]), escape: false);
});

test('"new reservation for this guest" opens the reservation drawer with the guest chosen', function () {
    $apartment = Apartment::factory()->create(['capacity' => 2]);

    Livewire::withQueryParams(['cliente' => $this->elena->id])
        ->test(Search::class)
        ->assertSee(__('availability.for_guest', ['name' => 'Elena Romero']))
        ->assertSeeHtml('&quot;guestId&quot;:'.$this->elena->id);

    Livewire::test(CreateForm::class)
        ->call('open', '2026-12-10', '2026-12-12', false, false, 2, $apartment->id, $this->elena->id)
        ->assertSet('guest_id', $this->elena->id);
});

test('the reservation panel opens the guest file on top', function () {
    $reservation = Reservation::factory()->for($this->elena)->between('2026-12-10', '2026-12-12')->create();

    // "Ver ficha" opens the guest drawer on top of the panel, without leaving the page.
    $this->get(route('calendar', ['desde' => '2026-12-01', 'reserva' => $reservation->id]))
        ->assertSeeHtml("open-guest', { id: {$this->elena->id} }");
});
