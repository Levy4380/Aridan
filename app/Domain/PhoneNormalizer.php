<?php

namespace App\Domain;

/**
 * Turns the many ways an Argentine phone is written into one canonical form,
 * so "11 15 5555-0142", "011 5555-0142" and "+54 9 11 5555-0142" are the
 * same guest. Canonical form: 549 + the 10-digit national number
 * (area code + subscriber number, without the trunk 0 nor the mobile 15).
 *
 * Numbers that don't look Argentine (other lengths, other country codes)
 * are kept as digits only, so they still work as a unique key.
 */
class PhoneNormalizer
{
    public static function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $international = str_starts_with($digits, '54') && strlen($digits) >= 12;

        $national = match (true) {
            $international => substr($digits, 2),
            str_starts_with($digits, '0') => substr($digits, 1),
            default => $digits,
        };

        // The mobile "9" goes after the country code.
        if ($international && str_starts_with($national, '9') && strlen($national) === 11) {
            $national = substr($national, 1);
        }

        $national = self::withoutMobilePrefix($national);

        return strlen($national) === 10 ? '549'.$national : $digits;
    }

    /**
     * Drops the local mobile prefix "15" that goes after the area code
     * (2 to 4 digits): 11 15 5555-0142 -> 11 5555-0142.
     */
    private static function withoutMobilePrefix(string $national): string
    {
        if (strlen($national) !== 12) {
            return $national;
        }

        foreach ([2, 3, 4] as $areaLength) {
            if (substr($national, $areaLength, 2) === '15') {
                return substr($national, 0, $areaLength).substr($national, $areaLength + 2);
            }
        }

        return $national;
    }
}
