<?php

namespace App\Queries;

use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Payments received (of señadas or pagadas reservations) that were not
 * invoiced yet, one row each: a deposit by transfer and the rest in cash
 * are invoiced separately. Cancelled reservations are included: the money
 * was received anyway.
 *
 * By default only bank transfers; cash is added on request. A payment
 * without a method (a data gap, already warned when saving) always shows up,
 * so it isn't forgotten.
 */
class PendingInvoicesQuery
{
    /**
     * @return Collection<int, Payment>
     */
    public function __invoke(bool $includeCash = false): Collection
    {
        return Payment::query()
            ->select('payments.*')
            ->join('reservations', 'reservations.id', '=', 'payments.reservation_id')
            ->whereNull('payments.invoiced_at')
            ->whereIn('reservations.payment_status', [PaymentStatus::Deposit, PaymentStatus::Paid])
            ->where(function (Builder $query) use ($includeCash) {
                $query->where('payments.method', PaymentMethod::Transfer)
                    ->orWhereNull('payments.method');

                if ($includeCash) {
                    $query->orWhere('payments.method', PaymentMethod::Cash);
                }
            })
            ->with(['reservation.guest', 'reservation.apartment', 'reservation.employee'])
            ->orderBy('reservations.check_in_date')
            ->orderBy('payments.reservation_id')
            ->orderBy('payments.id')
            ->get();
    }
}
