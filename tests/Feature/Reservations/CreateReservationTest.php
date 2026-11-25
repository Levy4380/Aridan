<?php

use App\Actions\Reservations\CreateReservation;
use App\Domain\GuestLookup;
use App\Domain\PaymentLine;
use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Domain\ReservationWarnings;
use App\Domain\Warning;
use App\Models\Apartment;
use App\Models\Extra;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->employee = User::factory()->create();
    $this->apartment = Apartment::factory()->create(['number' => '205', 'capacity' => 3]);
    $this->guest = Guest::factory()->create(['first_name' => 'Elena', 'last_name' => 'Romero', 'phone' => '11 5555-0142', 'document_number' => '30.123.456']);
});

function reservationInput(array $overrides = []): array
{
    return [
        'apartment_id' => test()->apartment->id,
        'employee_id' => test()->employee->id,
        'check_in_date' => '2026-12-10',
        'check_out_date' => '2026-12-12',
        'early_check_in' => false,
        'late_check_out' => false,
        'people_count' => 3,
        'payment_status' => 'deposit',
        'total_amount' => 250000,
        'payments' => [['amount' => 100000, 'method' => 'transfer']],
        'extra_mattresses' => 0,
        'is_movable' => true,
        'notes' => '',
        'extra_ids' => [],
        'guest_id' => test()->guest->id,
        'guest' => null,
        ...$overrides,
    ];
}

function errorsOf(callable $callback): array
{
    try {
        $callback();
    } catch (ValidationException $e) {
        return $e->errors();
    }

    return [];
}

test('a reservation is created for an existing guest, with its extras', function () {
    $parking = Extra::create(['name' => 'Cochera']);

    $reservation = app(CreateReservation::class)(reservationInput(['extra_ids' => [$parking->id], 'notes' => ' Puede ser sin balcón ']));

    expect($reservation->guest->is($this->guest))->toBeTrue()
        ->and($reservation->employee->is($this->employee))->toBeTrue()
        ->and($reservation->payment_status)->toBe(PaymentStatus::Deposit)
        ->and($reservation->total_amount)->toBe('250000.00')
        ->and($reservation->paidAmount())->toBe(100000.0)
        ->and($reservation->balance())->toBe(150000.0)
        ->and($reservation->notes)->toBe('Puede ser sin balcón')
        ->and($reservation->extras->pluck('name')->all())->toBe(['Cochera']);
});

test('a new guest is created together with the reservation', function () {
    $reservation = app(CreateReservation::class)(reservationInput([
        'guest_id' => null,
        'guest' => ['first_name' => ' Lucía ', 'last_name' => 'López', 'phone' => '351 15 555-0142', 'document_number' => ''],
    ]));

    expect($reservation->guest->full_name)->toBe('Lucía López')
        ->and($reservation->guest->phone)->toBe('5493515550142')
        ->and($reservation->guest->document_number)->toBeNull()
        ->and(Guest::count())->toBe(2);
});

test('the responsible employee can be someone other than who loads it', function () {
    $other = User::factory()->create();

    $reservation = app(CreateReservation::class)(reservationInput(['employee_id' => $other->id]));

    expect($reservation->employee->is($other))->toBeTrue();
});

test('an inactive employee can not be the responsible', function () {
    $inactive = User::factory()->inactive()->create();

    expect(errorsOf(fn () => app(CreateReservation::class)(reservationInput(['employee_id' => $inactive->id]))))
        ->toHaveKey('employee_id');
});

test('overlapping another active reservation is blocked with who occupies it', function () {
    Reservation::factory()->for($this->apartment)->for($this->guest)->between('2026-12-11', '2026-12-14')->create();

    $errors = errorsOf(fn () => app(CreateReservation::class)(reservationInput()));

    expect($errors['reservation'][0])->toContain('205')->toContain('Elena Romero')
        ->and(Reservation::count())->toBe(1);
});

test('same-day turnover is allowed, early check-in on a checkout morning is not', function () {
    Reservation::factory()->for($this->apartment)->between('2026-12-08', '2026-12-10')->create();

    app(CreateReservation::class)(reservationInput());

    expect(errorsOf(fn () => app(CreateReservation::class)(reservationInput([
        'check_in_date' => '2026-12-12', 'check_out_date' => '2026-12-14', 'early_check_in' => true,
    ]))))->toHaveKey('reservation');
});

test('an inactive apartment can not be booked', function () {
    $this->apartment->update(['is_active' => false]);

    expect(errorsOf(fn () => app(CreateReservation::class)(reservationInput())))->toHaveKey('reservation');
});

test('check-out must be after check-in', function () {
    expect(errorsOf(fn () => app(CreateReservation::class)(reservationInput(['check_out_date' => '2026-12-10']))))
        ->toHaveKey('check_out_date');
});

test('a new guest can not repeat a phone or a document, however it is written', function () {
    $phone = errorsOf(fn () => app(CreateReservation::class)(reservationInput([
        'guest_id' => null,
        'guest' => ['first_name' => 'Otra', 'last_name' => 'Persona', 'phone' => '+54 9 11 5555 0142', 'document_number' => ''],
    ])));
    $document = errorsOf(fn () => app(CreateReservation::class)(reservationInput([
        'guest_id' => null,
        'guest' => ['first_name' => 'Otra', 'last_name' => 'Persona', 'phone' => '11 4444-0000', 'document_number' => '30123456'],
    ])));

    expect($phone['guest.phone'][0])->toContain('Elena Romero')
        ->and($document['guest.document_number'][0])->toContain('Elena Romero')
        ->and(Guest::count())->toBe(1);
});

test('a reservation without payment keeps no payments nor total', function () {
    $reservation = app(CreateReservation::class)(reservationInput(['payment_status' => 'pending']));

    expect($reservation->payments)->toBeEmpty()
        ->and($reservation->total_amount)->toBeNull()
        ->and($reservation->paidAmount())->toBeNull();
});

test('a reservation can be paid in parts with different methods', function () {
    $reservation = app(CreateReservation::class)(reservationInput([
        'payment_status' => 'paid',
        'total_amount' => 999,
        'payments' => [
            ['amount' => 80000, 'method' => 'transfer', 'is_invoiced' => true],
            ['amount' => 120000, 'method' => 'cash'],
        ],
    ]), $this->employee);

    [$deposit, $rest] = $reservation->payments->all();

    expect($reservation->paidAmount())->toBe(200000.0)
        // The total is only kept while señada.
        ->and($reservation->total_amount)->toBeNull()
        ->and($deposit->method)->toBe(PaymentMethod::Transfer)
        ->and($deposit->isInvoiced())->toBeTrue()
        ->and($deposit->invoiced_by_id)->toBe($this->employee->id)
        ->and($rest->method)->toBe(PaymentMethod::Cash)
        ->and($rest->isInvoiced())->toBeFalse();
});

test('a payment needs a positive amount, and only payments of the same reservation can be kept', function () {
    expect(errorsOf(fn () => app(CreateReservation::class)(reservationInput(['payments' => [['amount' => 0, 'method' => 'cash']]]))))
        ->toHaveKey('payments.0.amount');

    $other = Reservation::factory()->deposit()->create();

    expect(errorsOf(fn () => app(CreateReservation::class)(reservationInput(['payments' => [['id' => $other->payments->first()->id, 'amount' => 1]]]))))
        ->toHaveKey('payments.0.id');
});

test('more people than capacity is allowed but warned, unless mattresses cover it', function () {
    $warnings = app(ReservationWarnings::class);

    expect($warnings->capacity($this->apartment, 4, 0)?->code)->toBe(Warning::CAPACITY_EXCEEDED)
        ->and($warnings->capacity($this->apartment, 4, 1))->toBeNull();

    $reservation = app(CreateReservation::class)(reservationInput(['people_count' => 5]));
    expect($reservation->people_count)->toBe(5);
});

test('a paid reservation without amount or method in any payment is warned', function () {
    $warnings = app(ReservationWarnings::class);
    $transfer = new PaymentLine(80000, PaymentMethod::Transfer);

    expect($warnings->missingAmount(PaymentStatus::Paid, [])?->code)->toBe(Warning::MISSING_AMOUNT)
        ->and($warnings->missingAmount(PaymentStatus::Paid, [new PaymentLine(null, null)])?->code)->toBe(Warning::MISSING_AMOUNT)
        ->and($warnings->missingAmount(PaymentStatus::Paid, [$transfer, new PaymentLine(120000, null)])?->code)->toBe(Warning::MISSING_AMOUNT)
        ->and($warnings->missingAmount(PaymentStatus::Paid, [$transfer, new PaymentLine(null, PaymentMethod::Cash)])?->code)->toBe(Warning::MISSING_AMOUNT)
        ->and($warnings->missingAmount(PaymentStatus::Paid, [$transfer, new PaymentLine(120000, PaymentMethod::Cash)]))->toBeNull()
        // An empty extra row is ignored.
        ->and($warnings->missingAmount(PaymentStatus::Deposit, [$transfer, new PaymentLine(null, null)]))->toBeNull()
        ->and($warnings->missingAmount(PaymentStatus::Pending, []))->toBeNull();
});

test('a señada reservation without total is warned, and so is one whose payments reach the total', function () {
    $warnings = app(ReservationWarnings::class);
    $deposit = [new PaymentLine(80000, PaymentMethod::Transfer)];
    $all = [...$deposit, new PaymentLine(120000, PaymentMethod::Cash)];

    expect($warnings->missingTotal(PaymentStatus::Deposit, null)?->code)->toBe(Warning::MISSING_TOTAL)
        ->and($warnings->missingTotal(PaymentStatus::Deposit, 200000))->toBeNull()
        ->and($warnings->missingTotal(PaymentStatus::Paid, null))->toBeNull()
        ->and($warnings->depositCoversTotal(PaymentStatus::Deposit, $deposit, 200000))->toBeNull()
        ->and($warnings->depositCoversTotal(PaymentStatus::Deposit, $all, 200000)?->code)->toBe(Warning::DEPOSIT_COVERS_TOTAL)
        ->and($warnings->depositCoversTotal(PaymentStatus::Deposit, $all, 150000)?->code)->toBe(Warning::DEPOSIT_COVERS_TOTAL)
        // Pagado is chosen by hand: nothing to warn.
        ->and($warnings->depositCoversTotal(PaymentStatus::Paid, $all, null))->toBeNull();
});

test('a new guest with the name of another guest is warned', function () {
    $warning = app(ReservationWarnings::class)->similarGuestName(['first_name' => 'elena', 'last_name' => 'ROMERO', 'phone' => '11 4444-0000']);

    expect($warning?->code)->toBe(Warning::SIMILAR_GUEST_NAME);
});

test('guests are found by phone or document however they are typed', function () {
    $lookup = app(GuestLookup::class);

    expect($lookup->find('011 15 5555-0142')?->is($this->guest))->toBeTrue()
        ->and($lookup->find('30.123.456')?->is($this->guest))->toBeTrue()
        ->and($lookup->find('123'))->toBeNull()
        ->and(GuestLookup::looksLikePhone('11 5555-0142'))->toBeTrue()
        ->and(GuestLookup::looksLikePhone('30.123.456'))->toBeFalse()
        ->and(GuestLookup::looksLikePhone('20-30123456-7'))->toBeFalse();
});
