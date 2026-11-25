<?php

namespace App\Livewire\Billing;

use App\Actions\Reservations\SetInvoiced;
use App\Models\Payment;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * What's needed to invoice a payment: its amount, the guest's CUIT/DNI and
 * name, and the "Facturado" button. Mounted in the app layout and opened
 * with `open-invoice` (a payment id).
 */
class InvoiceDrawer extends Component
{
    public ?int $paymentId = null;

    public bool $show = false;

    #[On('open-invoice')]
    public function open(int $id): void
    {
        $this->paymentId = $id;
        unset($this->payment);
        $this->show = $this->payment !== null;
    }

    public function updatedShow(bool $show): void
    {
        if (! $show) {
            $this->paymentId = null;
        }
    }

    #[Computed]
    public function payment(): ?Payment
    {
        return $this->paymentId
            ? Payment::with(['reservation.guest', 'reservation.apartment', 'reservation.payments', 'invoicedBy'])->find($this->paymentId)
            : null;
    }

    /** The guest's CUIT/DNI may have been loaded meanwhile, from their file. */
    #[On('guest-changed')]
    #[On('reservation-changed')]
    public function refresh(): void
    {
        unset($this->payment);
    }

    public function markInvoiced(SetInvoiced $setInvoiced): void
    {
        $payment = $this->payment;

        if (! $payment) {
            return;
        }

        $setInvoiced($payment, true, auth()->user());

        $this->show = false;
        $this->paymentId = null;
        $this->dispatch('reservation-changed');

        Flux::toast(variant: 'success', text: __('billing.marked', ['guest' => $payment->reservation->guest->full_name]));
    }

    public function render(): View
    {
        return view('livewire.billing.invoice-drawer');
    }
}
