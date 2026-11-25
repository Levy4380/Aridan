<?php

namespace App\Queries;

use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reservations with money received (señadas or pagadas) that were not
 * invoiced yet. Cancelled ones are included: the money was received anyway.
 *
 * By default only bank transfers; cash is added on request. A payment
 * without a method (a data gap, already warned when saving) always shows up,
 * so it isn't forgotten.
 */
class PendingInvoicesQuery
{
    /**
     * @return Collection<int, Reservation>
     */
    public function __invoke(bool $includeCash = false): Collection
    {
        return Reservation::withTrashed()
            ->whereNull('invoiced_at')
            ->whereIn('payment_status', [PaymentStatus::Deposit, PaymentStatus::Paid])
            ->where(function (Builder $query) use ($includeCash) {
                $query->where('payment_method', PaymentMethod::Transfer)
                    ->orWhereNull('payment_method');

                if ($includeCash) {
                    $query->orWhere('payment_method', PaymentMethod::Cash);
                }
            })
            ->with(['guest', 'apartment', 'employee'])
            ->orderBy('check_in_date')
            ->orderBy('id')
            ->get();
    }
}
