<?php

namespace App\Domain;

use App\Models\Guest;

/**
 * Finds an existing guest by phone or by DNI/CUIT, whatever the employee
 * typed (dots, dashes, spaces, +54, 15...).
 */
class GuestLookup
{
    public function find(?string $term): ?Guest
    {
        $digits = Guest::digits($term);

        if (strlen($digits) < 6) {
            return null;
        }

        return Guest::query()
            ->where('phone', PhoneNormalizer::normalize($term))
            ->orWhere('document_number', $digits)
            ->first();
    }

    public function byPhone(?string $phone, ?int $exceptId = null): ?Guest
    {
        $normalized = PhoneNormalizer::normalize($phone);

        return $normalized === '' ? null : Guest::query()
            ->where('phone', $normalized)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->first();
    }

    public function byDocument(?string $document, ?int $exceptId = null): ?Guest
    {
        $digits = Guest::digits($document);

        return $digits === '' ? null : Guest::query()
            ->where('document_number', $digits)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->first();
    }

    /**
     * Whether the term looks like a phone rather than a DNI/CUIT, to prefill
     * the right field when loading a new guest.
     */
    public static function looksLikePhone(?string $term): bool
    {
        $digits = Guest::digits($term);

        if (str_starts_with(trim((string) $term), '+') || str_starts_with($digits, '0')) {
            return true;
        }

        // DNI: 7-8 digits. CUIT: 11 digits. Phones: 10 or more, but not 11.
        return strlen($digits) >= 10 && strlen($digits) !== 11;
    }
}
