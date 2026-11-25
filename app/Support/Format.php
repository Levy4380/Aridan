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

    /**
     * A canonical phone (549 + 10 digits) shown as "11 5555-0142" or
     * "351 555-0142". Anything else is shown as stored.
     */
    public static function phone(?string $phone): string
    {
        $phone = (string) $phone;

        if (! preg_match('/^549(\d{10})$/', $phone, $match)) {
            return $phone;
        }

        $national = $match[1];

        return str_starts_with($national, '11')
            ? substr($national, 0, 2).' '.substr($national, 2, 4).'-'.substr($national, 6)
            : substr($national, 0, 3).' '.substr($national, 3, 3).'-'.substr($national, 6);
    }

    /**
     * DNI as 30.123.456; CUIT as 20-30123456-7.
     */
    public static function document(?string $document): string
    {
        $document = (string) $document;

        return match (true) {
            strlen($document) === 11 => substr($document, 0, 2).'-'.substr($document, 2, 8).'-'.substr($document, 10),
            in_array(strlen($document), [7, 8], true) => number_format((int) $document, 0, ',', '.'),
            default => $document,
        };
    }

    /**
     * Parses "$ 80.000", "80000", "80.000" or "80.000,50" into a number.
     */
    public static function parseMoney(int|float|string|null $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^\d,]/', '', $value);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : null;
    }

    public static function people(int $count): string
    {
        return __('reservations.people_short', ['count' => $count]);
    }
}
