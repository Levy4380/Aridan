<?php

use App\Actions\Guests\DeleteGuest;
use App\Livewire\Guests\Drawer;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->guest = Guest::factory()->create(['first_name' => 'Elena', 'last_name' => 'Romero']);
});

test('a guest without reservations is deleted for good', function () {
    app(DeleteGuest::class)($this->guest);

    expect(Guest::find($this->guest->id))->toBeNull();
});

test('a guest with reservations, even only cancelled ones, can not be deleted', function () {
    Reservation::factory()->for($this->guest)->create()->delete();

    expect(fn () => app(DeleteGuest::class)($this->guest))->toThrow(ValidationException::class)
        ->and(Guest::find($this->guest->id))->not->toBeNull();
});

test('the drawer confirms before deleting', function () {
    Livewire::test(Drawer::class)
        ->call('open', $this->guest->id)
        ->call('startDelete')
        ->assertSee(__('guests.delete.question', ['name' => 'Elena Romero']))
        ->call('delete')
        ->assertSet('show', false)
        ->assertDispatched('guest-changed');

    expect(Guest::find($this->guest->id))->toBeNull();
});

test('the drawer explains why a guest with reservations can not be deleted', function () {
    Reservation::factory()->for($this->guest)->create();

    Livewire::test(Drawer::class)
        ->call('open', $this->guest->id)
        ->call('startDelete')
        ->assertSee(trans_choice('guests.delete.blocked', 1, ['count' => 1]))
        ->assertDontSee(__('guests.delete.confirm'));
});
