<?php

namespace App\Actions\Apartments;

use App\Concerns\ApartmentValidationRules;
use App\Models\Apartment;
use Illuminate\Support\Facades\Validator;

/**
 * Edits the apartment's data. Activation goes through ActivateApartment /
 * DeactivateApartment, because deactivating has its own blocking rule.
 */
class UpdateApartment
{
    use ApartmentValidationRules;

    /**
     * @param  array{number: string, capacity: int|string, description?: string|null, sort_order: int|string}  $input
     */
    public function __invoke(Apartment $apartment, array $input): Apartment
    {
        $input['number'] = trim((string) ($input['number'] ?? ''));

        $validated = Validator::make($input, $this->apartmentRules($apartment->id))->validate();

        $apartment->update([
            ...$validated,
            'description' => filled($validated['description'] ?? null) ? $validated['description'] : null,
        ]);

        return $apartment;
    }
}
