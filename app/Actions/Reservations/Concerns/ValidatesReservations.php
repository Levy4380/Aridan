<?php

namespace App\Actions\Reservations\Concerns;

use App\Domain\GuestLookup;
use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Domain\StayRange;
use App\Models\Apartment;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Format;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The blocking rules shared by the reservation actions. Errors only: the
 * warnings are computed before calling the actions.
 */
trait ValidatesReservations
{
    /**
     * Validates and normalizes a full reservation payload.
     *
     * @param  array<string, mixed>  $input
     * @param  Reservation|null  $existing  when editing: its current responsible may stay even if inactive
     * @return array<string, mixed>
     */
    protected function validateReservation(array $input, ?Reservation $existing = null): array
    {
        $newGuest = empty($input['guest_id']);

        $data = Validator::make($input, [
            'apartment_id' => ['required', 'integer', Rule::exists(Apartment::class, 'id')],
            'employee_id' => ['required', 'integer', function (string $attribute, mixed $value, Closure $fail) use ($existing) {
                $keepsInactive = $existing && (int) $value === $existing->employee_id;

                if (! $keepsInactive && ! User::active()->whereKey($value)->exists()) {
                    $fail(__('reservations.errors.employee_inactive'));
                }
            }],
            'check_in_date' => ['required', 'date_format:Y-m-d'],
            'check_out_date' => ['required', 'date_format:Y-m-d', 'after:check_in_date'],
            'early_check_in' => ['boolean'],
            'late_check_out' => ['boolean'],
            'people_count' => ['required', 'integer', 'min:1', 'max:30'],
            'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
            'total_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'payments' => ['array', 'max:20'],
            'payments.*.id' => ['nullable', 'integer', function (string $attribute, mixed $value, Closure $fail) use ($existing) {
                // Only payments of this same reservation can be kept.
                if (! $existing || ! $existing->payments()->whereKey($value)->exists()) {
                    $fail(__('reservations.errors.payment_unknown'));
                }
            }],
            'payments.*.amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'payments.*.method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'payments.*.is_invoiced' => ['boolean'],
            'extra_mattresses' => ['integer', 'min:0', 'max:10'],
            'is_movable' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'extra_ids' => ['array'],
            'extra_ids.*' => ['integer', Rule::exists('extras', 'id')],
            'guest_id' => ['nullable', 'integer', Rule::exists(Guest::class, 'id')],
            'guest.first_name' => [Rule::requiredIf($newGuest), 'nullable', 'string', 'max:255'],
            'guest.last_name' => [Rule::requiredIf($newGuest), 'nullable', 'string', 'max:255'],
            'guest.phone' => [Rule::requiredIf($newGuest), 'nullable', 'string', 'max:40'],
            'guest.document_number' => ['nullable', 'string', 'max:20'],
        ], [], [
            'guest.first_name' => __('reservations.fields.first_name'),
            'guest.last_name' => __('reservations.fields.last_name'),
            'guest.phone' => __('reservations.fields.phone'),
            'people_count' => __('reservations.fields.people'),
            'total_amount' => __('reservations.fields.total_amount'),
            'payments.*.amount' => __('reservations.fields.amount'),
            'payments.*.method' => __('reservations.fields.payment_method'),
        ])->validate();

        if ($newGuest) {
            $this->ensureNewGuestIsUnique($data['guest']);
        }

        $status = PaymentStatus::from($data['payment_status']);

        return [
            ...$data,
            'early_check_in' => (bool) ($data['early_check_in'] ?? false),
            'late_check_out' => (bool) ($data['late_check_out'] ?? false),
            'extra_mattresses' => (int) ($data['extra_mattresses'] ?? 0),
            'is_movable' => (bool) ($data['is_movable'] ?? true),
            'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
            'extra_ids' => $data['extra_ids'] ?? [],
            // Payments only once something was paid; the total only when señada.
            'total_amount' => $status->hasTotal() ? ($data['total_amount'] ?? null) : null,
            'payments' => $status->hasPayment() ? array_values(array_map(fn (array $payment) => [
                'id' => isset($payment['id']) ? (int) $payment['id'] : null,
                'amount' => $payment['amount'],
                'method' => $payment['method'] ?? null,
                'is_invoiced' => (bool) ($payment['is_invoiced'] ?? false),
            ], $data['payments'] ?? [])) : [],
            'guest_id' => $newGuest ? null : (int) $data['guest_id'],
            'guest' => $newGuest ? [
                'first_name' => trim($data['guest']['first_name']),
                'last_name' => trim($data['guest']['last_name']),
                'phone' => $data['guest']['phone'],
                'document_number' => $data['guest']['document_number'] ?? null,
            ] : null,
        ];
    }

    /**
     * Leaves the reservation with exactly these payments: keeps the ones
     * with an id (and their invoicing), adds the new ones and deletes the
     * rest. "Facturado" keeps who marked it and when.
     *
     * @param  list<array{id: int|null, amount: mixed, method: string|null, is_invoiced: bool}>  $rows
     */
    protected function syncPayments(Reservation $reservation, array $rows, ?User $by): void
    {
        $existing = $reservation->payments()->get()->keyBy('id');
        $kept = [];

        foreach ($rows as $row) {
            /** @var Payment $payment */
            $payment = ($row['id'] ? $existing->get($row['id']) : null) ?? $reservation->payments()->make();
            $payment->fill(['amount' => $row['amount'], 'method' => $row['method']]);

            if ($row['is_invoiced'] !== $payment->isInvoiced()) {
                $payment->invoiced_at = $row['is_invoiced'] ? now() : null;
                $payment->invoiced_by_id = $row['is_invoiced'] ? $by?->id : null;
            }

            $payment->save();
            $kept[] = $payment->id;
        }

        $reservation->payments()->whereKeyNot($kept)->delete();
        $reservation->unsetRelation('payments');
    }

    /**
     * @param  array<string, mixed>  $guest
     */
    protected function ensureNewGuestIsUnique(array $guest): void
    {
        $lookup = app(GuestLookup::class);

        if ($existing = $lookup->byPhone($guest['phone'])) {
            throw ValidationException::withMessages(['guest.phone' => __('reservations.errors.phone_taken', ['name' => $existing->full_name])]);
        }

        if (filled($guest['document_number'] ?? null) && $existing = $lookup->byDocument($guest['document_number'])) {
            throw ValidationException::withMessages(['guest.document_number' => __('reservations.errors.document_taken', ['name' => $existing->full_name])]);
        }
    }

    /**
     * Locks the apartment and checks it can take the stay: active and with
     * no other active reservation on any of its half-days.
     */
    protected function ensureApartmentCanTake(int $apartmentId, StayRange $stay, ?int $exceptReservationId = null, bool $mustBeActive = true): Apartment
    {
        // Lock the apartment: two employees can't book it at the same time.
        $apartment = Apartment::lockForUpdate()->findOrFail($apartmentId);

        if ($mustBeActive && ! $apartment->is_active) {
            throw ValidationException::withMessages(['reservation' => __('reservations.errors.apartment_inactive', ['number' => $apartment->number])]);
        }

        $conflict = $apartment->reservations()
            ->overlappingSlots($stay->startSlot, $stay->endSlot)
            ->when($exceptReservationId, fn ($query) => $query->whereKeyNot($exceptReservationId))
            ->with('guest')
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages(['reservation' => __('reservations.errors.overlap', [
                'number' => $apartment->number,
                'guest' => $conflict->guest->full_name,
                'from' => Format::date($conflict->check_in_date),
                'to' => Format::date($conflict->check_out_date),
            ])]);
        }

        return $apartment;
    }

    /**
     * Runs the write and turns the database exclusion constraint (an overlap
     * the check missed) into the same overlap error.
     *
     * @template T
     *
     * @param  Closure(): T  $write
     * @return T
     */
    protected function guardingOverlap(Closure $write): mixed
    {
        try {
            return $write();
        } catch (QueryException $e) {
            if ($e->getCode() === '23P01') {
                throw ValidationException::withMessages(['reservation' => __('reservations.errors.overlap_generic')]);
            }

            throw $e;
        }
    }
}
