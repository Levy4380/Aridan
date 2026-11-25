<?php

namespace Database\Seeders;

use App\Models\Apartment;
use Illuminate\Database\Seeder;

/**
 * The apart's 18 apartments (CLAUDE.md, "Departamentos"). Safe to run in
 * production and more than once: it only creates the ones that are missing,
 * and never touches apartments edited from the app.
 */
class ApartmentSeeder extends Seeder
{
    /** @var array<string, int> number => capacity */
    public const APARTMENTS = [
        '102' => 3, '104' => 2, '105' => 4, '106' => 2, '107' => 2,
        '201' => 2, '202' => 4, '203' => 5, '204' => 2, '205' => 4, '206' => 2, '207' => 4,
        '301' => 3, '303' => 5, '304' => 2, '305' => 4, '306' => 2, '307' => 5,
    ];

    public function run(): void
    {
        $order = 0;

        foreach (self::APARTMENTS as $number => $capacity) {
            $order += 10;

            Apartment::firstOrCreate(
                ['number' => (string) $number],
                ['capacity' => $capacity, 'is_active' => true, 'sort_order' => $order],
            );
        }
    }
}
