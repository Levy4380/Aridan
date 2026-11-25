<?php

namespace App\Livewire\Billing;

use App\Actions\Reservations\SetInvoiced;
use App\Models\Reservation;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * What's needed to issue the invoice of a reservation: amount received,
 * the guest's CUIT/DNI and name, and the "Facturada" button. Mounted in the
 * app layout and opened with `open-invoice`.
 */
class InvoiceDrawer extends Component
{
    public ?int $reservationId = null;

    public bool $show = false;

    #[On('open-invoice')]
    public function open(int $id): void
    {
        $this->reservationId = $id;
        unset($this->reservation);
        $this->show = $this->reservation !== null;
    }

    public function updatedShow(bool $show): void
    {
        if (! $show) {
            $this->reservationId = null;
        }
    }

    #[Computed]
    public function reservation(): ?Reservation
    {
        return $this->reservationId
            ? Reservation::withTrashed()->with(['guest', 'apartment', 'invoicedBy'])->find($this->reservationId)
            : null;
    }

    /** The guest's CUIT/DNI may have been loaded meanwhile, from their file. */
    #[On('guest-changed')]
    #[On('reservation-changed')]
    public function refresh(): void
    {
        unset($this->reservation);
    }

    public function markInvoiced(SetInvoiced $setInvoiced): void
    {
        $reservation = $this->reservation;

        if (! $reservation) {
            return;
        }

        $setInvoiced($reservation, true, auth()->user());

        $this->show = false;
        $this->reservationId = null;
        $this->dispatch('reservation-changed');

        Flux::toast(variant: 'success', text: __('billing.marked', ['guest' => $reservation->guest->full_name]));
    }

    public function render(): View
    {
        return view('livewire.billing.invoice-drawer');
    }
}
