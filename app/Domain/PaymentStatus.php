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
     * Whether payments (amount and method) are expected.
     */
    public function hasPayment(): bool
    {
        return $this !== self::Pending;
    }

    /**
     * Only a señada reservation keeps the total agreed: paid, the total is
     * what was received; reserved, nothing was agreed in money yet.
     */
    public function hasTotal(): bool
    {
        return $this === self::Deposit;
    }
}
