<?php

namespace App\Actions\Reservations;

use App\Domain\GuestLookup;
use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Domain\StayRange;
use App\Models\Apartment;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Format;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Creates a reservation, and the guest too when it's a new one.
 *
 * Blocks (errors) only on: overlap with another active reservation,
 * inactive apartment, check-out not after check-in, a new guest that
 * repeats a phone or DNI/CUIT, or an inactive responsible employee.
 * Everything else is a warning, computed before calling this action.
 * Never trusts the UI: everything is validated again here.
 */
class CreateReservation
{
    public function __construct(private GuestLookup $guests) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(array $input): Reservation
    {
        $data = $this->validate($input);

        try {
            return DB::transaction(function () use ($data) {
                // Lock the apartment: two employees can't book it at the same time.
                $apartment = Apartment::lockForUpdate()->findOrFail($data['apartment_id']);

                if (! $apartment->is_active) {
                    throw ValidationException::withMessages(['reservation' => __('reservations.errors.apartment_inactive', ['number' => $apartment->number])]);
                }

                $stay = StayRange::make($data['check_in_date'], $data['check_out_date'], $data['early_check_in'], $data['late_check_out']);
                $this->ensureNoOverlap($apartment, $stay);

                $guestId = $data['guest_id'] ?? Guest::create($data['guest'])->id;

                $reservation = Reservation::create([
                    ...collect($data)->except(['guest', 'guest_id', 'extra_ids'])->all(),
                    'guest_id' => $guestId,
                ]);

                $reservation->extras()->sync($data['extra_ids']);

                return $reservation;
            });
        } catch (QueryException $e) {
            // The database exclusion constraint caught an overlap the check missed.
            if ($e->getCode() === '23P01') {
                throw ValidationException::withMessages(['reservation' => __('reservations.errors.overlap_generic')]);
            }

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function validate(array $input): array
    {
        $newGuest = empty($input['guest_id']);

        $data = Validator::make($input, [
            'apartment_id' => ['required', 'integer', Rule::exists(Apartment::class, 'id')],
            'employee_id' => ['required', 'integer', Rule::exists(User::class, 'id')->where('is_active', true)],
            'check_in_date' => ['required', 'date_format:Y-m-d'],
            'check_out_date' => ['required', 'date_format:Y-m-d', 'after:check_in_date'],
            'early_check_in' => ['boolean'],
            'late_check_out' => ['boolean'],
            'people_count' => ['required', 'integer', 'min:1', 'max:30'],
            'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
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
        ], [
            'employee_id.exists' => __('reservations.errors.employee_inactive'),
        ], [
            'guest.first_name' => __('reservations.fields.first_name'),
            'guest.last_name' => __('reservations.fields.last_name'),
            'guest.phone' => __('reservations.fields.phone'),
            'people_count' => __('reservations.fields.people'),
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
            // The amount and method only make sense once something was paid.
            'amount' => $status->hasPayment() ? ($data['amount'] ?? null) : null,
            'payment_method' => $status->hasPayment() ? ($data['payment_method'] ?? null) : null,
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
     * @param  array<string, mixed>  $guest
     */
    private function ensureNewGuestIsUnique(array $guest): void
    {
        if ($existing = $this->guests->byPhone($guest['phone'])) {
            throw ValidationException::withMessages(['guest.phone' => __('reservations.errors.phone_taken', ['name' => $existing->full_name])]);
        }

        if (filled($guest['document_number'] ?? null) && $existing = $this->guests->byDocument($guest['document_number'])) {
            throw ValidationException::withMessages(['guest.document_number' => __('reservations.errors.document_taken', ['name' => $existing->full_name])]);
        }
    }

    private function ensureNoOverlap(Apartment $apartment, StayRange $stay, ?int $exceptId = null): void
    {
        $conflict = $apartment->reservations()
            ->overlappingSlots($stay->startSlot, $stay->endSlot)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
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
    }
}
