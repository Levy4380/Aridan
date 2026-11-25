<?php

namespace App\Livewire\Billing;

use App\Models\Payment;
use App\Queries\PendingInvoicesQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * "Pendientes a facturar" (/pendientes-a-facturar): payments received and
 * not invoiced, one row each. By default only transfers; cash can be added.
 * A row opens the Billing\InvoiceDrawer.
 */
class Pending extends Component
{
    #[Url(as: 'efectivo')]
    public bool $includeCash = false;

    /**
     * @return Collection<int, Payment>
     */
    #[Computed]
    public function payments(): Collection
    {
        return app(PendingInvoicesQuery::class)($this->includeCash);
    }

    #[On('reservation-changed')]
    #[On('guest-changed')]
    public function refresh(): void
    {
        unset($this->payments);
    }

    public function render(): View
    {
        return view('livewire.billing.pending')->title(__('billing.title'));
    }
}
