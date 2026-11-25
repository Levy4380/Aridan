<?php

namespace App\Domain;

enum PaymentMethod: string
{
    case Transfer = 'transfer';
    case Cash = 'cash';

    public function label(): string
    {
        return __('reservations.payment_method.'.$this->value);
    }
}
