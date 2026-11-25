<?php

use App\Actions\Reservations\SetInvoiced;
use App\Domain\PaymentMethod;
use App\Livewire\Billing\InvoiceDrawer;
use App\Livewire\Billing\Pending;
use App\Livewire\Reservations\CreateForm;
use App\Livewire\Reservations\Panel;
use App\Models\Apartment;
use App\Models\Guest;
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

test('by default only transfers with money received and not invoiced are pending', function () {
    $transferPaid = Reservation::factory()->for($this->guest)->paid(200000, PaymentMethod::Transfer)->create();
    $transferDeposit = Reservation::factory()->for($this->guest)->deposit(50000, PaymentMethod::Transfer)->create();
    $cash = Reservation::factory()->for($this->guest)->paid(100000, PaymentMethod::Cash)->create();
    $noPayment = Reservation::factory()->for($this->guest)->create(); // Reservado
    $invoiced = Reservation::factory()->for($this->guest)->paid(1, PaymentMethod::Transfer)->create(['invoiced_at' => now()]);

    expect(pending())->toEqualCanonicalizing([$transferPaid->id, $transferDeposit->id])
        ->and(pending(cash: true))->toEqualCanonicalizing([$transferPaid->id, $transferDeposit->id, $cash->id])
        ->and(pending(cash: true))->not->toContain($noPayment->id)
        ->and(pending(cash: true))->not->toContain($invoiced->id);
});

test('cancelled reservations with money received are still pending', function () {
    $cancelled = Reservation::factory()->for($this->guest)->paid(200000)->create();
    $cancelled->delete();

    expect(pending())->toBe([$cancelled->id]);
});

test('a payment without a method shows up so it is not forgotten', function () {
    $missing = Reservation::factory()->for($this->guest)->create(['payment_status' => 'paid', 'amount' => 1000, 'payment_method' => null]);

    expect(pending())->toBe([$missing->id]);
});

test('the page lists them with status badges and a cash filter in the URL', function () {
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

test('the drawer shows amount, CUIT/DNI and name, and "Facturada" takes it off the list', function () {
    $reservation = Reservation::factory()->for($this->guest)->paid(200000)->create();

    Livewire::test(InvoiceDrawer::class)
        ->call('open', $reservation->id)
        ->assertSet('show', true)
        ->assertSee('$200.000')
        ->assertSee('20-30123456-7')
        ->assertSee('Elena Romero')
        ->call('markInvoiced')
        ->assertSet('show', false)
        ->assertDispatched('reservation-changed');

    expect($reservation->fresh())
        ->isInvoiced()->toBeTrue()
        ->invoiced_by_id->toBe($this->me->id)
        ->and(pending())->toBe([]);
});

test('a guest without DNI/CUIT is warned in the drawer', function () {
    $reservation = Reservation::factory()->for(Guest::factory()->state(['document_number' => null]))->paid()->create();

    Livewire::test(InvoiceDrawer::class)
        ->call('open', $reservation->id)
        ->assertSee(__('billing.drawer.no_document_help'));
});

test('"Facturada" is next to the payment method when creating and editing', function () {
    $apartment = Apartment::factory()->create(['capacity' => 2]);

    Livewire::test(CreateForm::class)
        ->call('open', '2026-12-10', '2026-12-12', false, false, 2, $apartment->id, $this->guest->id)
        ->set('payment_status', 'paid')
        ->set('payment_method', 'transfer')
        ->set('amount', '100000')
        ->set('is_invoiced', true)
        ->call('save');

    $reservation = Reservation::sole();
    expect($reservation->isInvoiced())->toBeTrue();

    Livewire::test(Panel::class)
        ->call('open', $reservation->id)
        ->call('startEdit')
        ->assertSet('is_invoiced', true)
        ->set('is_invoiced', false)
        ->call('save');

    expect($reservation->fresh()->isInvoiced())->toBeFalse();
});

test('it can always be changed from the panel, even on a cancelled reservation', function () {
    $reservation = Reservation::factory()->for($this->guest)->create(); // not even paid
    $reservation->delete();

    Livewire::test(Panel::class)
        ->call('open', $reservation->id)
        ->call('toggleInvoiced')
        ->assertSee(__('reservations.invoicing.marked'));

    expect(Reservation::withTrashed()->find($reservation->id)->isInvoiced())->toBeTrue();

    app(SetInvoiced::class)(Reservation::withTrashed()->find($reservation->id), false, $this->me);
    expect(Reservation::withTrashed()->find($reservation->id)->invoiced_by_id)->toBeNull();
});
