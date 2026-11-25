<?php

namespace App\Concerns;

use App\Models\Apartment;
use Illuminate\Validation\Rule;

trait ApartmentValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function apartmentRules(?int $apartmentId = null): array
    {
        return [
            'number' => [
                'required', 'string', 'max:20',
                Rule::unique(Apartment::class, 'number')->ignore($apartmentId),
            ],
            'capacity' => ['required', 'integer', 'min:1', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
