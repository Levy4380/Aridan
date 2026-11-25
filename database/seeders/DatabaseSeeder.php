<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Production only gets the catalogs
     * (apartments and extras); the base starts with no reservations.
     */
    public function run(): void
    {
        $this->call([ApartmentSeeder::class, ExtraSeeder::class]);

        if (app()->environment('local')) {
            // Test employee and demo data for local development only. Real
            // employees are created with `php artisan employee:create`.
            User::firstOrCreate(['email' => 'test@example.com'], [
                'name' => 'Empleado de prueba',
                'password' => 'password',
                'color' => '#4fc3f7',
                'email_verified_at' => now(),
            ]);

            $this->call(DemoSeeder::class);
        }
    }
}
