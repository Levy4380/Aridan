<?php

namespace App\Domain;

use App\Models\Apartment;
use App\Models\Guest;
use App\Support\Format;

/**
 * Computes the non-blocking warnings of a reservation before it's saved.
 * Errors (overlap, inactive apartment, bad dates) are the actions' job.
 */
class ReservationWarnings
{
    /**
     * @param  array{first_name?: string, last_name?: string, phone?: string}|null  $newGuest  only when a guest is being created
     * @return list<Warning>
     */
    public function forNew(
        Apartment $apartment,
        int $people,
        int $extraMattresses,
        PaymentStatus $paymentStatus,
        int|float|string|null $amount,
        ?PaymentMethod $paymentMethod,
        ?array $newGuest = null,
    ): array {
        return array_values(array_filter([
            $this->capacity($apartment, $people, $extraMattresses),
            $this->missingAmount($paymentStatus, $amount, $paymentMethod),
            $newGuest ? $this->similarGuestName($newGuest) : null,
        ]));
    }

    public function capacity(Apartment $apartment, int $people, int $extraMattresses): ?Warning
    {
        $missing = $people - ($apartment->capacity + $extraMattresses);

        if ($missing <= 0) {
            return null;
        }

        return new Warning(Warning::CAPACITY_EXCEEDED, trans_choice('warnings.capacity_exceeded', $missing, [
            'number' => $apartment->number,
            'capacity' => $apartment->capacity,
            'people' => $people,
            'count' => $missing,
        ]));
    }

    public function missingAmount(PaymentStatus $status, int|float|string|null $amount, ?PaymentMethod $method): ?Warning
    {
        if (! $status->hasPayment() || (filled($amount) && (float) $amount > 0 && $method !== null)) {
            return null;
        }

        return new Warning(Warning::MISSING_AMOUNT, __('warnings.missing_amount', ['status' => $status->label()]));
    }

    /**
     * A new guest with the same name as an existing one but another phone:
     * it may be the same person with a new number.
     *
     * @param  array{first_name?: string, last_name?: string, phone?: string}  $newGuest
     */
    public function similarGuestName(array $newGuest): ?Warning
    {
        $first = trim($newGuest['first_name'] ?? '');
        $last = trim($newGuest['last_name'] ?? '');

        if ($first === '' || $last === '') {
            return null;
        }

        $existing = Guest::query()
            ->whereRaw('lower(first_name) = ?', [mb_strtolower($first)])
            ->whereRaw('lower(last_name) = ?', [mb_strtolower($last)])
            ->where('phone', '!=', PhoneNormalizer::normalize($newGuest['phone'] ?? ''))
            ->first();

        return $existing ? new Warning(Warning::SIMILAR_GUEST_NAME, __('warnings.similar_guest_name', [
            'name' => $existing->full_name,
            'phone' => Format::phone($existing->phone),
        ])) : null;
    }
}
