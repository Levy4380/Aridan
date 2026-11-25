<?php

namespace App\Actions\Apartments;

use App\Concerns\ApartmentValidationRules;
use App\Models\Apartment;
use Illuminate\Support\Facades\Validator;

class CreateApartment
{
    use ApartmentValidationRules;

    /**
     * @param  array{number: string, capacity: int|string, description?: string|null, sort_order: int|string}  $input
     */
    public function __invoke(array $input): Apartment
    {
        $input['number'] = trim((string) ($input['number'] ?? ''));

        $validated = Validator::make($input, $this->apartmentRules())->validate();

        return Apartment::create([
            ...$validated,
            'description' => filled($validated['description'] ?? null) ? $validated['description'] : null,
            'is_active' => true,
        ]);
    }
}
