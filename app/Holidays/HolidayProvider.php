<?php

namespace App\Holidays;

/**
 * Source of Argentina's public holidays. If the API changes, only the
 * implementation bound in AppServiceProvider changes.
 */
interface HolidayProvider
{
    /**
     * @return list<array{date: string, name: string, type: string|null}> date as Y-m-d
     *
     * @throws HolidayProviderException when the source can't be read
     */
    public function forYear(int $year): array;
}
