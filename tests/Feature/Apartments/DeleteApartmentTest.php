<?php

use App\Actions\Apartments\DeleteApartment;
use App\Livewire\Apartments\Drawer;
use App\Models\Apartment;
use App\Models\Extra;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 10:00'));
    $this->actingAs(User::factory()->create());
    $this->apartment = Apartment::factory()->create(['number' => '205']);
});

test('an apartment without reservations is deleted for good', function () {
    app(DeleteApartment::class)($this->apartment);

    expect(Apartment::find($this->apartment->id))->toBeNull();
});

test('it can not be deleted with a future or current reservation', function (string $from, string $to) {
    Reservation::factory()->for($this->apartment)->between($from, $to)->create();

    expect(fn () => app(DeleteApartment::class)($this->apartment))->toThrow(ValidationException::class)
        ->and(Apartment::find($this->apartment->id))->not->toBeNull();
})->with([
    'future' => ['2026-12-10', '2026-12-12'],
    'in progress' => ['2026-11-23', '2026-11-27'],
]);

test('past and cancelled reservations are deleted with it, with their payments and extras', function () {
    $past = Reservation::factory()->for($this->apartment)->between('2026-10-01', '2026-10-05')->paid()->create();
    $past->extras()->attach(Extra::create(['name' => 'Cochera']));
    $cancelled = Reservation::factory()->for($this->apartment)->between('2026-12-10', '2026-12-12')->create();
    $cancelled->delete();
    $other = Reservation::factory()->between('2026-10-01', '2026-10-05')->create();

    app(DeleteApartment::class)($this->apartment);

    expect(Apartment::find($this->apartment->id))->toBeNull()
        ->and(Reservation::withTrashed()->whereKey([$past->id, $cancelled->id])->exists())->toBeFalse()
        ->and(Payment::where('reservation_id', $past->id)->exists())->toBeFalse()
        ->and($other->fresh())->not->toBeNull();
});

test('the drawer explains why it is blocked, or what goes with it', function () {
    Reservation::factory()->for($this->apartment)->between('2026-10-01', '2026-10-05')->create();
    $future = Reservation::factory()->for($this->apartment)->between('2026-12-10', '2026-12-12')->create();

    Livewire::test(Drawer::class)
        ->call('open', $this->apartment->id)
        ->call('startDelete')
        ->assertSee(trans_choice('apartments.delete.blocked', 1, ['count' => 1]))
        ->assertDontSee(__('apartments.delete.confirm'));

    $future->delete(); // cancelled: it no longer blocks

    Livewire::test(Drawer::class)
        ->call('open', $this->apartment->id)
        ->call('startDelete')
        ->assertSee(trans_choice('apartments.delete.with_reservations', 2, ['count' => 2]))
        ->call('delete')
        ->assertSet('show', false)
        ->assertDispatched('apartment-changed');

    expect(Apartment::find($this->apartment->id))->toBeNull();
});
