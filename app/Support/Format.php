<?php

namespace App\Support;

use DateTimeInterface;

/**
 * Display formats (arquitectura-frontend.md, 6): Argentine pesos, short
 * dates and counters. Views never format by hand.
 */
class Format
{
    /**
     * $200.000 — dot as thousands separator, no decimals when they are zero.
     */
    public static function money(int|float|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        $amount = (float) $amount;
        $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;

        return '$'.number_format($amount, $decimals, ',', '.');
    }

    /**
     * 10/11 or 10/11/2026.
     */
    public static function date(DateTimeInterface $date, bool $withYear = false): string
    {
        return $date->format($withYear ? 'd/m/Y' : 'd/m');
    }

    public static function people(int $count): string
    {
        return __('reservations.people_short', ['count' => $count]);
    }
}
