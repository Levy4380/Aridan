<?php

namespace App\Livewire\Forms;

use App\Concerns\ApartmentValidationRules;
use App\Models\Apartment;
use Livewire\Form;

class ApartmentForm extends Form
{
    use ApartmentValidationRules;

    public ?Apartment $apartment = null;

    public string $number = '';

    public ?int $capacity = null;

    public string $description = '';

    public ?int $sort_order = null;

    public bool $is_active = true;

    public function fillFrom(Apartment $apartment): void
    {
        $this->apartment = $apartment;
        $this->number = $apartment->number;
        $this->capacity = $apartment->capacity;
        $this->description = $apartment->description ?? '';
        $this->sort_order = $apartment->sort_order;
        $this->is_active = $apartment->is_active;
    }

    public function startNew(int $nextSortOrder): void
    {
        $this->reset();
        $this->sort_order = $nextSortOrder;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            ...$this->apartmentRules($this->apartment?->id),
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'number' => $this->number,
            'capacity' => $this->capacity,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'number' => __('apartments.fields.number'),
            'capacity' => __('apartments.fields.capacity'),
            'description' => __('apartments.fields.description'),
            'sort_order' => __('apartments.fields.sort_order'),
        ];
    }
}
