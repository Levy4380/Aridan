<?php

use App\Actions\Reservations\AddPayment;
use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Livewire\Reservations\Panel;
use App\Models\Apartment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 10:00'));
    $this->me = User::factory()->create();
    $this->actingAs($this->me);
    $this->apartment = Apartment::factory()->create(['number' => '205']);
});

function depositOf(int $paid, ?int $total): Reservation
{
    return Reservation::factory()->for(test()->apartment)->between('2026-12-10', '2026-12-12')->deposit($paid, total: $total)->create();
}

describe('action', function () {
    test('adds a payment, keeping the existing ones, and sets the chosen status', function () {
        $reservation = depositOf(80000, 200000);

        app(AddPayment::class)($reservation, ['amount' => 120000, 'method' => 'cash', 'is_invoiced' => true, 'payment_status' => 'paid'], $this->me);

        $reservation->refresh();
        $rest = $reservation->payments->last();

        expect($reservation->payments)->toHaveCount(2)
            ->and($reservation->payment_status)->toBe(PaymentStatus::Paid)
            // Pagada: the total is only kept while señada.
            ->and($reservation->total_amount)->toBeNull()
            ->and($rest->method)->toBe(PaymentMethod::Cash)
            ->and($rest->isInvoiced())->toBeTrue()
            ->and($rest->invoiced_by_id)->toBe($this->me->id);
    });

    test('a señada one keeps its total unless a new one is given', function () {
        $reservation = depositOf(50000, 200000);

        app(AddPayment::class)($reservation, ['amount' => 30000, 'payment_status' => 'deposit'], $this->me);

        expect($reservation->fresh()->total_amount)->toBe('200000.00')
            ->and($reservation->fresh()->paidAmount())->toBe(80000.0);
    });

    test('it needs a positive amount, señado or pagado, and an active reservation', function (array $input, bool $cancel) {
        $reservation = depositOf(50000, 200000);

        if ($cancel) {
            $reservation->delete();
        }

        expect(fn () => app(AddPayment::class)($reservation, $input, $this->me))->toThrow(ValidationException::class)
            ->and($reservation->payments()->count())->toBe(1);
    })->with([
        'no amount' => [['amount' => 0, 'payment_status' => 'deposit'], false],
        'reservado' => [['amount' => 1000, 'payment_status' => 'pending'], false],
        'cancelled' => [['amount' => 1000, 'payment_status' => 'deposit'], true],
    ]);
});

describe('panel', function () {
    test('the rest in cash is added from the detail, offering to mark it as paid', function () {
        $reservation = depositOf(80000, 200000);

        Livewire::test(Panel::class)
            ->call('open', $reservation->id)
            ->assertSee(__('reservations.panel.paid_total', ['amount' => '$80.000']))
            ->call('startAddPayment')
            ->assertSet('addingPayment', true)
            ->set('newPayment.amount', '120.000')
            ->set('newPayment.method', 'cash')
            ->assertSee(__('reservations.panel.mark_paid'))
            ->assertSet('markAsPaid', true)
            ->call('savePayment')
            ->assertHasNoErrors()
            ->assertSet('addingPayment', false)
            ->assertSee(__('reservations.panel.payment_saved', ['amount' => '$120.000', 'status' => 'Pagado']))
            ->assertDispatched('reservation-changed');

        expect($reservation->fresh())
            ->payment_status->toBe(PaymentStatus::Paid)
            ->paidAmount()->toBe(200000.0);
    });

    test('unticking "mark as paid" keeps it señada, after confirming the warning', function () {
        $reservation = depositOf(80000, 200000);

        $panel = Livewire::test(Panel::class)
            ->call('open', $reservation->id)
            ->call('startAddPayment')
            ->set('newPayment.amount', '120.000')
            ->set('newPayment.method', 'cash')
            ->set('markAsPaid', false)
            ->call('savePayment')
            ->assertSee(__('warnings.deposit_covers_total', ['paid' => '$200.000', 'total' => '$200.000']))
            ->assertSee(__('reservations.create.save_anyway'));

        expect($reservation->payments()->count())->toBe(1);

        $panel->call('savePayment')->assertSet('addingPayment', false);

        expect($reservation->fresh()->payment_status)->toBe(PaymentStatus::Deposit)
            ->and($reservation->payments()->count())->toBe(2);
    });

    test('a partial payment does not offer marking it as paid', function () {
        $reservation = depositOf(50000, 200000);

        Livewire::test(Panel::class)
            ->call('open', $reservation->id)
            ->call('startAddPayment')
            ->set('newPayment.amount', '50.000')
            ->set('newPayment.method', 'transfer')
            ->assertDontSee(__('reservations.panel.mark_paid'))
            ->assertSee(__('reservations.create.balance', ['amount' => '$100.000']))
            ->call('savePayment');

        expect($reservation->fresh()->payment_status)->toBe(PaymentStatus::Deposit)
            ->and($reservation->fresh()->paidAmount())->toBe(100000.0);
    });

    test('a reservado one asks what it becomes, and the total when señada', function () {
        $reservation = Reservation::factory()->for($this->apartment)->between('2026-12-10', '2026-12-12')->create();

        Livewire::test(Panel::class)
            ->call('open', $reservation->id)
            ->assertSee(__('reservations.panel.no_payments'))
            ->call('startAddPayment')
            ->assertSee(__('reservations.panel.becomes'))
            ->assertSee(__('reservations.fields.total_amount'))
            ->set('newPaymentTotal', '300.000')
            ->set('newPayment.amount', '100.000')
            ->set('newPayment.method', 'transfer')
            ->call('savePayment')
            ->assertHasNoErrors();

        expect($reservation->fresh())
            ->payment_status->toBe(PaymentStatus::Deposit)
            ->total_amount->toBe('300000.00')
            ->paidAmount()->toBe(100000.0);
    });

    test('the amount is required', function () {
        $reservation = depositOf(50000, 200000);

        Livewire::test(Panel::class)
            ->call('open', $reservation->id)
            ->call('startAddPayment')
            ->call('savePayment')
            ->assertHasErrors('newPayment.amount');
    });

    test('a cancelled reservation shows its payments but can not get new ones', function () {
        $reservation = depositOf(50000, 200000);
        $reservation->delete();

        Livewire::test(Panel::class)
            ->call('open', $reservation->id)
            ->assertSee('$50.000')
            ->assertDontSee(__('reservations.panel.add_payment'))
            ->call('startAddPayment')
            ->assertSet('addingPayment', false);
    });
});
