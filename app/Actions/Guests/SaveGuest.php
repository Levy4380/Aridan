<?php

namespace App\Actions\Guests;

use App\Domain\GuestLookup;
use App\Domain\PhoneNormalizer;
use App\Models\Guest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Creates or edits a guest from the Guests screen. Phone required and
 * unique; DNI/CUIT optional but unique. Uniqueness is checked on the
 * normalized values, so "11 15 5555-0142" and "+54 9 11 5555 0142" clash.
 */
class SaveGuest
{
    public function __construct(private GuestLookup $guests) {}

    /**
     * @param  array{first_name: string, last_name: string, phone: string, document_number?: string|null}  $input
     */
    public function __invoke(array $input, ?Guest $guest = null): Guest
    {
        $data = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40', function ($attribute, $value, $fail) {
                if (PhoneNormalizer::normalize($value) === '') {
                    $fail(__('guests.errors.phone_invalid'));
                }
            }],
            'document_number' => ['nullable', 'string', 'max:20'],
        ], [], [
            'first_name' => __('reservations.fields.first_name'),
            'last_name' => __('reservations.fields.last_name'),
            'phone' => __('reservations.fields.phone'),
            'document_number' => __('reservations.fields.document'),
        ])->validate();

        return DB::transaction(function () use ($data, $guest) {
            if ($owner = $this->guests->byPhone($data['phone'], $guest?->id)) {
                throw ValidationException::withMessages(['phone' => __('reservations.errors.phone_taken', ['name' => $owner->full_name])]);
            }

            if (filled($data['document_number'] ?? null) && $owner = $this->guests->byDocument($data['document_number'], $guest?->id)) {
                throw ValidationException::withMessages(['document_number' => __('reservations.errors.document_taken', ['name' => $owner->full_name])]);
            }

            $attributes = [
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'phone' => $data['phone'],
                'document_number' => $data['document_number'] ?? null,
            ];

            if ($guest) {
                $guest->update($attributes);

                return $guest;
            }

            return Guest::create($attributes);
        });
    }
}
