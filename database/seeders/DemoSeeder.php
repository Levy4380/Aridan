<?php

namespace Database\Seeders;

use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Models\Apartment;
use App\Models\Extra;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Fake data to try the app locally: a few employees, guests and two months
 * of non-overlapping reservations around today. Never runs in production
 * (DatabaseSeeder only calls it in the local environment).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Reservation::withTrashed()->exists()) {
            return;
        }

        $employees = collect([
            ['Tomás', 'tomas@example.com', '#4fc3f7'],
            ['Rodrigo', 'rodrigo@example.com', '#81c784'],
            ['Valentina', 'valentina@example.com', '#f06292'],
            ['Víctor', 'victor@example.com', '#ffb74d'],
        ])->map(fn (array $data) => User::firstOrCreate(['email' => $data[1]], [
            'name' => $data[0],
            'password' => 'password',
            'color' => $data[2],
            'email_verified_at' => now(),
        ]));

        $guests = Guest::factory()->count(40)->create();
        $extras = Extra::all();

        $from = CarbonImmutable::today()->subDays(25);
        $until = CarbonImmutable::today()->addDays(45);

        foreach (Apartment::active()->ordered()->get() as $apartment) {
            $day = $from->addDays(fake()->numberBetween(0, 4));

            while ($day->lessThan($until)) {
                $nights = fake()->randomElement([1, 2, 2, 3, 3, 4, 5, 7]);
                $checkOut = $day->addDays($nights);
                $gapAfter = fake()->randomElement([0, 0, 1, 1, 2, 3, 5]);
                $status = fake()->randomElement(PaymentStatus::cases());

                $reservation = Reservation::create([
                    'apartment_id' => $apartment->id,
                    'guest_id' => $guests->random()->id,
                    'employee_id' => $employees->random()->id,
                    'check_in_date' => $day,
                    'check_out_date' => $checkOut,
                    // Early/late only when there is a free day to take.
                    'early_check_in' => false,
                    'late_check_out' => $gapAfter > 0 && fake()->boolean(15),
                    'people_count' => max(1, $apartment->capacity + fake()->randomElement([-1, 0, 0, 0, 1])),
                    'payment_status' => $status,
                    'amount' => $status->hasPayment() ? fake()->randomElement([50000, 80000, 100000, 150000, 200000, 300000]) : null,
                    'payment_method' => $status->hasPayment() ? fake()->randomElement(PaymentMethod::cases()) : null,
                    'extra_mattresses' => 0,
                    'is_movable' => fake()->boolean(60),
                    'notes' => fake()->optional(0.2)->randomElement(['Puede ser sin balcón', 'Llegan tarde', 'Reserva familiar', 'Es cliente']),
                ]);

                if ($extras->isNotEmpty() && fake()->boolean(30)) {
                    $reservation->extras()->attach($extras->random(fake()->numberBetween(1, $extras->count()))->pluck('id'));
                }

                $day = $checkOut->addDays($gapAfter);
            }
        }
    }
}
