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
     * @param  list<PaymentLine>  $payments
     * @param  array{first_name?: string, last_name?: string, phone?: string}|null  $newGuest  only when a guest is being created
     * @return list<Warning>
     */
    public function forNew(
        Apartment $apartment,
        int $people,
        int $extraMattresses,
        PaymentStatus $paymentStatus,
        array $payments,
        ?float $totalAmount,
        ?array $newGuest = null,
    ): array {
        return array_values(array_filter([
            $this->capacity($apartment, $people, $extraMattresses),
            $this->missingAmount($paymentStatus, $payments),
            $this->missingTotal($paymentStatus, $totalAmount),
            $this->depositCoversTotal($paymentStatus, $payments, $totalAmount),
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

    /**
     * Señada or pagada, but no money loaded, or a payment without its
     * amount or its method.
     *
     * @param  list<PaymentLine>  $payments
     */
    public function missingAmount(PaymentStatus $status, array $payments): ?Warning
    {
        if (! $status->hasPayment()) {
            return null;
        }

        $received = array_filter($payments, fn (PaymentLine $line) => $line->hasAmount());
        $incomplete = array_filter($payments, fn (PaymentLine $line) => $line->hasAmount() !== ($line->method !== null));

        if ($received !== [] && $incomplete === []) {
            return null;
        }

        return new Warning(Warning::MISSING_AMOUNT, __('warnings.missing_amount', ['status' => $status->label()]));
    }

    /**
     * Señada without the total of the reservation: then nobody knows how
     * much is left to collect.
     */
    public function missingTotal(PaymentStatus $status, ?float $totalAmount): ?Warning
    {
        if (! $status->hasTotal() || ($totalAmount !== null && $totalAmount > 0)) {
            return null;
        }

        return new Warning(Warning::MISSING_TOTAL, __('warnings.missing_total'));
    }

    /**
     * Señada, but what was received already reaches the total. The status
     * is never changed on its own: the employee decides.
     *
     * @param  list<PaymentLine>  $payments
     */
    public function depositCoversTotal(PaymentStatus $status, array $payments, ?float $totalAmount): ?Warning
    {
        $paid = PaymentLine::sum($payments);

        if (! $status->hasTotal() || $totalAmount === null || $totalAmount <= 0 || $paid < $totalAmount) {
            return null;
        }

        return new Warning(Warning::DEPOSIT_COVERS_TOTAL, __('warnings.deposit_covers_total', [
            'paid' => Format::money($paid),
            'total' => Format::money($totalAmount),
        ]));
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
