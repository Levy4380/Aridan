<?php

namespace App\Domain;

enum PaymentStatus: string
{
    /** Reservado: no payment yet. */
    case Pending = 'pending';

    /** Señado: partial payment. */
    case Deposit = 'deposit';

    /** Pagado: paid in full. */
    case Paid = 'paid';

    public function label(): string
    {
        return __('reservations.payment_status.'.$this->value);
    }

    /**
     * Whether an amount and payment method are expected.
     */
    public function hasPayment(): bool
    {
        return $this !== self::Pending;
    }
}
