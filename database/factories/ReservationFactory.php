<?php

namespace Database\Factories;

use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Models\Apartment;
use App\Models\Guest;
use App\Models\Payment;
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
            'total_amount' => null,
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

    /**
     * Señada, with one payment and optionally the total agreed.
     */
    public function deposit(int $amount = 100000, ?PaymentMethod $method = PaymentMethod::Transfer, ?int $total = null): static
    {
        return $this->state(fn () => [
            'payment_status' => PaymentStatus::Deposit,
            'total_amount' => $total,
        ])->withPayment($amount, $method);
    }

    public function paid(int $amount = 200000, ?PaymentMethod $method = PaymentMethod::Transfer): static
    {
        return $this->state(fn () => ['payment_status' => PaymentStatus::Paid])->withPayment($amount, $method);
    }

    /**
     * One more payment, once the reservation is created.
     */
    public function withPayment(int $amount, ?PaymentMethod $method = PaymentMethod::Transfer, bool $invoiced = false): static
    {
        return $this->afterCreating(function (Reservation $reservation) use ($amount, $method, $invoiced) {
            Payment::factory()
                ->for($reservation)
                ->state(['amount' => $amount, 'method' => $method])
                ->when($invoiced, fn (PaymentFactory $factory) => $factory->invoiced())
                ->create();
        });
    }

    public function fixed(): static
    {
        return $this->state(fn () => ['is_movable' => false]);
    }
}
