<?php

use App\Actions\Employees\DeleteEmployee;
use App\Livewire\Employees\Drawer;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 10:00'));
    $this->me = User::factory()->create(['name' => 'Tomás']);
    $this->actingAs($this->me);
    $this->rodrigo = User::factory()->create(['name' => 'Rodrigo']);
});

test('an employee without reservations is deleted for good', function () {
    app(DeleteEmployee::class)($this->rodrigo, $this->me);

    expect(User::find($this->rodrigo->id))->toBeNull();
});

test('their reservations, cancelled ones too, pass to the chosen active employee', function () {
    $valentina = User::factory()->create();
    $future = Reservation::factory()->for($this->rodrigo, 'employee')->create();
    $cancelled = Reservation::factory()->for($this->rodrigo, 'employee')->create();
    $cancelled->delete();

    app(DeleteEmployee::class)($this->rodrigo, $this->me, $valentina->id);

    expect($future->fresh()->employee_id)->toBe($valentina->id)
        ->and(Reservation::withTrashed()->find($cancelled->id)->employee_id)->toBe($valentina->id)
        ->and(User::find($this->rodrigo->id))->toBeNull();
});

test('with reservations it needs an active heir other than themselves', function (?string $heir) {
    Reservation::factory()->for($this->rodrigo, 'employee')->create();
    $heirId = match ($heir) {
        'self' => $this->rodrigo->id,
        'inactive' => User::factory()->inactive()->create()->id,
        default => null,
    };

    expect(fn () => app(DeleteEmployee::class)($this->rodrigo, $this->me, $heirId))->toThrow(ValidationException::class)
        ->and(User::find($this->rodrigo->id))->not->toBeNull();
})->with(['none' => [null], 'self' => ['self'], 'inactive' => ['inactive']]);

test('nobody can delete themselves', function () {
    expect(fn () => app(DeleteEmployee::class)($this->me, $this->me))->toThrow(ValidationException::class);
});

test('who cancelled or invoiced is left blank, and their sessions are closed', function () {
    $reservation = Reservation::factory()->paid()->create();
    $reservation->forceFill(['cancelled_by_id' => $this->rodrigo->id])->save();
    $reservation->delete();
    $payment = $reservation->payments()->first();
    $payment->forceFill(['invoiced_at' => now(), 'invoiced_by_id' => $this->rodrigo->id])->save();
    DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $this->rodrigo->id, 'payload' => '', 'last_activity' => time()]);

    app(DeleteEmployee::class)($this->rodrigo, $this->me);

    expect(Reservation::withTrashed()->find($reservation->id)->cancelled_by_id)->toBeNull()
        ->and(Payment::find($payment->id))
        ->invoiced_by_id->toBeNull()
        ->isInvoiced()->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $this->rodrigo->id)->exists())->toBeFalse();
});

test('the drawer asks for confirmation and who takes the reservations', function () {
    $reservation = Reservation::factory()->for($this->rodrigo, 'employee')->create();

    Livewire::test(Drawer::class)
        ->call('open', $this->rodrigo->id)
        ->call('startDelete')
        ->assertSee(__('employees.delete.question', ['name' => 'Rodrigo']))
        ->assertSee(trans_choice('employees.delete.transfer', 1, ['count' => 1]))
        ->assertSet('transferTo', $this->me->id)
        ->call('delete')
        ->assertHasNoErrors()
        ->assertSet('show', false)
        ->assertDispatched('employee-changed');

    expect($reservation->fresh()->employee_id)->toBe($this->me->id)
        ->and(User::find($this->rodrigo->id))->toBeNull();
});

test('the drawer does not offer deleting yourself', function () {
    Livewire::test(Drawer::class)
        ->call('open', $this->me->id)
        ->assertDontSee(__('employees.delete.button'))
        ->call('startDelete')
        ->assertSet('confirmingDelete', false);
});
