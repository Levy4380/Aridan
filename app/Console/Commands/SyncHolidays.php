<?php

namespace App\Console\Commands;

use App\Holidays\HolidayProvider;
use App\Holidays\HolidayProviderException;
use App\Models\Holiday;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('holidays:sync {year? : First year to sync (defaults to the current one); the next year is synced too}')]
#[Description('Copy Argentina\'s holidays from the public API into the local holidays table')]
class SyncHolidays extends Command
{
    public function handle(HolidayProvider $provider): int
    {
        $year = (int) ($this->argument('year') ?? now()->year);
        $failed = false;

        foreach ([$year, $year + 1] as $current) {
            try {
                $holidays = $provider->forYear($current);
            } catch (HolidayProviderException $e) {
                // Keep the existing data: the calendar never depends on the API.
                Log::warning('Holiday sync failed', ['year' => $current, 'error' => $e->getMessage()]);
                $this->components->error("{$current}: {$e->getMessage()}");
                $failed = true;

                continue;
            }

            $rows = collect($holidays)
                ->unique('date')
                ->map(fn (array $holiday) => [...$holiday, 'created_at' => now(), 'updated_at' => now()])
                ->values()
                ->all();

            Holiday::upsert($rows, ['date'], ['name', 'type', 'updated_at']);

            $this->components->info("{$current}: ".count($rows).' feriados.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
