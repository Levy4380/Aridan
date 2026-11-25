<?php

namespace App\Actions\Reservations;

use App\Models\Payment;
use App\Models\User;

/**
 * Marks a payment as invoiced (facturado) or not. It can always be changed:
 * whatever the payment status, and also on cancelled reservations (the
 * money was received anyway). Keeps who and when, for control.
 */
class SetInvoiced
{
    public function __invoke(Payment $payment, bool $invoiced, User $by): Payment
    {
        if ($invoiced === $payment->isInvoiced()) {
            return $payment;
        }

        $payment->forceFill([
            'invoiced_at' => $invoiced ? now() : null,
            'invoiced_by_id' => $invoiced ? $by->id : null,
        ])->save();

        return $payment->refresh();
    }
}
