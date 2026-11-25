<?php

namespace Database\Factories;

use App\Domain\PaymentMethod;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'amount' => 100000,
            'method' => PaymentMethod::Transfer,
        ];
    }

    public function invoiced(?User $by = null): static
    {
        return $this->state(fn () => [
            'invoiced_at' => now(),
            'invoiced_by_id' => $by?->id,
        ]);
    }
}
