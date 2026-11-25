<?php

namespace Database\Seeders;

use App\Models\Extra;
use Illuminate\Database\Seeder;

/**
 * The extras catalog (first iteration: Cochera, Desayuno). Safe to run in
 * production and more than once.
 */
class ExtraSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('extras.seed') as $name) {
            Extra::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
