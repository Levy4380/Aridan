<?php

namespace App\Domain;

/**
 * One payment as typed in the form, before it's saved: what the warnings
 * look at. The amount is already parsed (null when empty or unreadable).
 */
final readonly class PaymentLine
{
    public function __construct(
        public ?float $amount,
        public ?PaymentMethod $method,
    ) {}

    public function hasAmount(): bool
    {
        return $this->amount !== null && $this->amount > 0;
    }

    /**
     * @param  iterable<PaymentLine>  $lines
     */
    public static function sum(iterable $lines): float
    {
        $total = 0.0;

        foreach ($lines as $line) {
            $total += $line->hasAmount() ? $line->amount : 0.0;
        }

        return $total;
    }
}
