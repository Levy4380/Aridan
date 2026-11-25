<?php

use App\Actions\Reservations\SetInvoiced;
use App\Domain\PaymentMethod;
use App\Livewire\Billing\InvoiceDrawer;
use App\Livewire\Billing\Pending;
use App\Livewire\Reservations\CreateForm;
use App\Livewire\Reservations\Panel;
use App\Models\Apartment;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Queries\PendingInvoicesQuery;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 10:00'));
    $this->me = User::factory()->create(['name' => 'Tomás']);
    $this->actingAs($this->me);
    $this->guest = Guest::factory()->create(['first_name' => 'Elena', 'last_name' => 'Romero', 'document_number' => '20301234567']);
});

function pending(bool $cash = false): array
{
    return app(PendingInvoicesQuery::class)($cash)->pluck('id')->all();
}

function paymentOf(Reservation $reservation, int $index = 0): Payment
{
    return $reservation->payments()->get()[$index];
}

test('by default only transfers received and not invoiced are pending', function () {
    $transferPaid = Reservation::factory()->for($this->guest)->paid(200000, PaymentMethod::Transfer)->create();
    $transferDeposit = Reservation::factory()->for($this->guest)->deposit(50000, PaymentMethod::Transfer)->create();
    $cash = Reservation::factory()->for($this->guest)->paid(100000, PaymentMethod::Cash)->create();
    Reservation::factory()->for($this->guest)->create(); // Reservado
    $invoiced = Reservation::factory()->for($this->guest)->withPayment(1, invoiced: true)->create(['payment_status' => 'paid']);

    expect(pending())->toEqualCanonicalizing([paymentOf($transferPaid)->id, paymentOf($transferDeposit)->id])
        ->and(pending(cash: true))->toEqualCanonicalizing([paymentOf($transferPaid)->id, paymentOf($transferDeposit)->id, paymentOf($cash)->id])
        ->and(pending(cash: true))->not->toContain(paymentOf($invoiced)->id);
});

test('each payment is pending on its own: the deposit by transfer, the rest in cash', function () {
    $reservation = Reservation::factory()->for($this->guest)
        ->paid(80000, PaymentMethod::Transfer)
        ->withPayment(120000, PaymentMethod::Cash)
        ->create();
    [$deposit, $rest] = $reservation->payments->all();

    expect(pending())->toBe([$deposit->id])
        ->and(pending(cash: true))->toBe([$deposit->id, $rest->id]);

    app(SetInvoiced::class)($deposit, true, $this->me);

    expect(pending(cash: true))->toBe([$rest->id]);
});

test('cancelled reservations with money received are still pending', function () {
    $cancelled = Reservation::factory()->for($this->guest)->paid(200000)->create();
    $cancelled->delete();

    expect(pending())->toBe([paymentOf($cancelled)->id]);
});

test('a payment without a method shows up so it is not forgotten', function () {
    $missing = Reservation::factory()->for($this->guest)->paid(1000, method: null)->create();

    expect(pending())->toBe([paymentOf($missing)->id]);
});

test('the page lists the payments with status badges and a cash filter in the URL', function () {
    Reservation::factory()->for($this->guest)->for(Apartment::factory()->state(['number' => '205']))->deposit(50000)->create();
    Reservation::factory()->for(Guest::factory()->state(['first_name' => 'Raúl', 'last_name' => 'Silva']))->paid(90000, PaymentMethod::Cash)->create();

    $this->get(route('billing.pending'))
        ->assertOk()
        ->assertSee(__('nav.billing'))
        ->assertSee('Elena Romero')
        ->assertSee('20-30123456-7')
        ->assertSee(__('reservations.payment_status.deposit'))
        ->assertSee('$50.000')
        ->assertDontSee('Raúl Silva');

    Livewire::test(Pending::class)
        ->set('includeCash', true)
        ->assertSee('Raúl Silva')
        ->assertSee('$90.000');
});

test('the drawer shows the payment, CUIT/DNI and name, and "Facturado" takes it off the list', function () {
    $reservation = Reservation::factory()->for($this->guest)->paid(80000)->withPayment(120000, PaymentMethod::Cash)->create();
    $payment = paymentOf($reservation);

    Livewire::test(InvoiceDrawer::class)
        ->call('open', $payment->id)
        ->assertSet('show', true)
        ->assertSee('$80.000')
        ->assertSee(trans_choice('billing.drawer.other_payments', 1, ['count' => 1, 'total' => '$200.000']))
        ->assertSee('20-30123456-7')
        ->assertSee('Elena Romero')
        ->call('markInvoiced')
        ->assertSet('show', false)
        ->assertDispatched('reservation-changed');

    expect($payment->fresh())
        ->isInvoiced()->toBeTrue()
        ->invoiced_by_id->toBe($this->me->id)
        ->and(pending())->toBe([]);
});

test('a guest without DNI/CUIT is warned in the drawer', function () {
    $reservation = Reservation::factory()->for(Guest::factory()->state(['document_number' => null]))->paid()->create();

    Livewire::test(InvoiceDrawer::class)
        ->call('open', paymentOf($reservation)->id)
        ->assertSee(__('billing.drawer.no_document_help'));
});

test('"Facturado" is next to each payment when creating and editing', function () {
    $apartment = Apartment::factory()->create(['capacity' => 2]);

    Livewire::test(CreateForm::class)
        ->call('open', '2026-12-10', '2026-12-12', false, false, 2, $apartment->id, $this->guest->id)
        ->set('payment_status', 'paid')
        ->set('payments.0.method', 'transfer')
        ->set('payments.0.amount', '100000')
        ->set('payments.0.is_invoiced', true)
        ->call('save');

    $reservation = Reservation::sole();
    expect(paymentOf($reservation)->isInvoiced())->toBeTrue();

    Livewire::test(Panel::class)
        ->call('open', $reservation->id)
        ->call('startEdit')
        ->assertSet('payments.0.is_invoiced', true)
        ->set('payments.0.is_invoiced', false)
        ->call('save');

    expect(paymentOf($reservation)->isInvoiced())->toBeFalse();
});

test('it can always be changed from the panel, even on a cancelled reservation', function () {
    $reservation = Reservation::factory()->for($this->guest)->paid(200000)->create();
    $reservation->delete();
    $payment = paymentOf($reservation);

    Livewire::test(Panel::class)
        ->call('open', $reservation->id)
        ->call('toggleInvoiced', $payment->id)
        ->assertSee(__('reservations.invoicing.marked'));

    expect($payment->fresh()->isInvoiced())->toBeTrue();

    app(SetInvoiced::class)($payment->fresh(), false, $this->me);
    expect($payment->fresh()->invoiced_by_id)->toBeNull();
});

test('the panel can not touch a payment of another reservation', function () {
    $reservation = Reservation::factory()->for($this->guest)->paid()->create();
    $other = paymentOf(Reservation::factory()->paid()->create());

    Livewire::test(Panel::class)
        ->call('open', $reservation->id)
        ->call('toggleInvoiced', $other->id);

    expect($other->fresh()->isInvoiced())->toBeFalse();
});
