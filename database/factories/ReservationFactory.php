<?php

namespace Database\Factories;

use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Models\Apartment;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = CarbonImmutable::today()->addDays(fake()->numberBetween(1, 60));

        return [
            'apartment_id' => Apartment::factory(),
            'guest_id' => Guest::factory(),
            'employee_id' => User::factory(),
            'check_in_date' => $checkIn,
            'check_out_date' => $checkIn->addDays(fake()->numberBetween(1, 7)),
            'early_check_in' => false,
            'late_check_out' => false,
            'people_count' => fake()->numberBetween(1, 4),
            'payment_status' => PaymentStatus::Pending,
            'amount' => null,
            'payment_method' => null,
            'extra_mattresses' => 0,
            'is_movable' => true,
            'notes' => null,
        ];
    }

    public function between(string $checkIn, string $checkOut): static
    {
        return $this->state(fn () => [
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
        ]);
    }

    public function deposit(int $amount = 100000, PaymentMethod $method = PaymentMethod::Transfer): static
    {
        return $this->state(fn () => [
            'payment_status' => PaymentStatus::Deposit,
            'amount' => $amount,
            'payment_method' => $method,
        ]);
    }

    public function paid(int $amount = 200000, PaymentMethod $method = PaymentMethod::Transfer): static
    {
        return $this->state(fn () => [
            'payment_status' => PaymentStatus::Paid,
            'amount' => $amount,
            'payment_method' => $method,
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn () => ['is_movable' => false]);
    }
}
