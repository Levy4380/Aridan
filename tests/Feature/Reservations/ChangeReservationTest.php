<?php

use App\Actions\Reservations\CancelReservation;
use App\Actions\Reservations\MoveReservation;
use App\Actions\Reservations\UpdateReservation;
use App\Domain\PaymentMethod;
use App\Models\Apartment;
use App\Models\Extra;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function payloadFrom(Reservation $reservation, array $overrides = []): array
{
    return [
        'apartment_id' => $reservation->apartment_id,
        'employee_id' => $reservation->employee_id,
        'check_in_date' => $reservation->check_in_date->toDateString(),
        'check_out_date' => $reservation->check_out_date->toDateString(),
        'early_check_in' => $reservation->early_check_in,
        'late_check_out' => $reservation->late_check_out,
        'people_count' => $reservation->people_count,
        'payment_status' => $reservation->payment_status->value,
        'total_amount' => $reservation->total_amount,
        'payments' => $reservation->payments->map(fn ($payment) => [
            'id' => $payment->id,
            'amount' => $payment->amount,
            'method' => $payment->method?->value,
            'is_invoiced' => $payment->isInvoiced(),
        ])->all(),
        'extra_mattresses' => $reservation->extra_mattresses,
        'is_movable' => $reservation->is_movable,
        'notes' => $reservation->notes,
        'extra_ids' => [],
        'guest_id' => $reservation->guest_id,
        'guest' => null,
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->apartment = Apartment::factory()->create(['number' => '205']);
    $this->reservation = Reservation::factory()->for($this->apartment)->between('2026-12-10', '2026-12-14')->create();
});

describe('update', function () {
    test('payment, conditions, note and extras can be edited', function () {
        $breakfast = Extra::create(['name' => 'Desayuno']);

        app(UpdateReservation::class)($this->reservation, payloadFrom($this->reservation, [
            'payment_status' => 'paid',
            'payments' => [['amount' => 200000, 'method' => 'cash']],
            'is_movable' => false,
            'notes' => 'Llegan tarde',
            'extra_ids' => [$breakfast->id],
        ]));

        expect($this->reservation->fresh())
            ->paidAmount()->toBe(200000.0)
            ->is_movable->toBeFalse()
            ->notes->toBe('Llegan tarde')
            ->and($this->reservation->fresh()->extras->pluck('name')->all())->toBe(['Desayuno']);
    });

    test('changing the dates checks overlap against the other reservations only', function () {
        app(UpdateReservation::class)($this->reservation, payloadFrom($this->reservation, ['check_out_date' => '2026-12-15']));
        expect($this->reservation->fresh()->check_out_date->toDateString())->toBe('2026-12-15');

        Reservation::factory()->for($this->apartment)->between('2026-12-16', '2026-12-18')->create();

        expect(fn () => app(UpdateReservation::class)($this->reservation, payloadFrom($this->reservation, ['check_out_date' => '2026-12-17'])))
            ->toThrow(ValidationException::class);
    });

    test('payments are kept by id with their invoicing, added and removed', function () {
        $reservation = Reservation::factory()->for($this->apartment)->between('2026-12-20', '2026-12-22')
            ->deposit(80000, total: 200000)->withPayment(5000, invoiced: true)->create();
        [$deposit, $mistake] = $reservation->payments->all();
        $mistake->update(['amount' => 5000]);

        app(UpdateReservation::class)($reservation, payloadFrom($reservation->fresh(), [
            'payment_status' => 'paid',
            'payments' => [
                ['id' => $deposit->id, 'amount' => 80000, 'method' => 'transfer', 'is_invoiced' => true],
                ['amount' => 120000, 'method' => 'cash'],
            ],
        ]), User::factory()->create());

        $payments = $reservation->fresh()->payments;

        expect($payments)->toHaveCount(2)
            ->and($payments[0]->id)->toBe($deposit->id)
            ->and($payments[0]->isInvoiced())->toBeTrue()
            ->and($payments[1]->amount)->toBe('120000.00')
            ->and($payments->pluck('id'))->not->toContain($mistake->id);
    });

    test('changing the dates keeps the payments as they were', function () {
        $reservation = Reservation::factory()->for($this->apartment)->between('2026-12-20', '2026-12-22')
            ->paid(80000)->withPayment(120000, PaymentMethod::Cash, invoiced: true)->create();
        $before = $reservation->payments->map->only(['id', 'amount', 'method', 'invoiced_at'])->all();

        app(UpdateReservation::class)($reservation, payloadFrom($reservation, ['check_out_date' => '2026-12-23']));

        expect($reservation->fresh()->payments->map->only(['id', 'amount', 'method', 'invoiced_at'])->all())->toEqual($before);
    });

    test('a deactivated responsible can stay, but a new responsible must be active', function () {
        $this->reservation->employee->update(['is_active' => false]);

        app(UpdateReservation::class)($this->reservation, payloadFrom($this->reservation, ['notes' => 'ok']));
        expect($this->reservation->fresh()->notes)->toBe('ok');

        $inactive = User::factory()->inactive()->create();

        expect(fn () => app(UpdateReservation::class)($this->reservation, payloadFrom($this->reservation, ['employee_id' => $inactive->id])))
            ->toThrow(ValidationException::class);
    });

    test('a past reservation in an apartment deactivated later can still be edited', function () {
        $this->apartment->update(['is_active' => false]);

        app(UpdateReservation::class)($this->reservation, payloadFrom($this->reservation, ['payment_status' => 'paid', 'payments' => [['amount' => 1, 'method' => 'cash']]]));

        expect($this->reservation->fresh()->payment_status->value)->toBe('paid');
    });

    test('the guest can be changed to another one', function () {
        $other = Guest::factory()->create();

        app(UpdateReservation::class)($this->reservation, payloadFrom($this->reservation, ['guest_id' => $other->id]));

        expect($this->reservation->fresh()->guest_id)->toBe($other->id);
    });
});

describe('move', function () {
    test('a reservation moves to a free apartment keeping its dates', function () {
        $destination = Apartment::factory()->create(['number' => '305']);

        $moved = app(MoveReservation::class)($this->reservation, $destination->id);

        expect($moved->apartment_id)->toBe($destination->id)
            ->and($moved->check_in_date->toDateString())->toBe('2026-12-10');
    });

    test('it can not move to a taken or inactive apartment', function () {
        $taken = Apartment::factory()->create();
        Reservation::factory()->for($taken)->between('2026-12-12', '2026-12-13')->create();
        $inactive = Apartment::factory()->inactive()->create();

        expect(fn () => app(MoveReservation::class)($this->reservation, $taken->id))->toThrow(ValidationException::class)
            ->and(fn () => app(MoveReservation::class)($this->reservation, $inactive->id))->toThrow(ValidationException::class)
            ->and($this->reservation->fresh()->apartment_id)->toBe($this->apartment->id);
    });
});

describe('cancel', function () {
    test('a cancelled reservation stays for control with who cancelled it, and frees the apartment', function () {
        $me = User::factory()->create();

        app(CancelReservation::class)($this->reservation, $me);

        $cancelled = Reservation::withTrashed()->find($this->reservation->id);

        expect($cancelled->trashed())->toBeTrue()
            ->and($cancelled->cancelled_by_id)->toBe($me->id)
            ->and(Reservation::count())->toBe(0);

        Reservation::factory()->for($this->apartment)->between('2026-12-10', '2026-12-14')->create();
        expect(Reservation::count())->toBe(1);
    });

    test('a cancelled reservation can not be edited or moved', function () {
        app(CancelReservation::class)($this->reservation, User::factory()->create());
        $cancelled = Reservation::withTrashed()->find($this->reservation->id);

        expect(fn () => app(UpdateReservation::class)($cancelled, payloadFrom($cancelled)))->toThrow(ValidationException::class)
            ->and(fn () => app(MoveReservation::class)($cancelled, Apartment::factory()->create()->id))->toThrow(ValidationException::class);
    });
});
