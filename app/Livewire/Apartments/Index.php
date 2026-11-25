<?php

namespace App\Livewire\Apartments;

use App\Actions\Apartments\ActivateApartment;
use App\Actions\Apartments\CreateApartment;
use App\Actions\Apartments\DeactivateApartment;
use App\Actions\Apartments\UpdateApartment;
use App\Livewire\Forms\ApartmentForm;
use App\Models\Apartment;
use App\Models\Reservation;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Apartments screen (/departamentos): list + create/edit modal.
 */
class Index extends Component
{
    public ApartmentForm $form;

    public bool $showForm = false;

    /**
     * @return Collection<int, Apartment>
     */
    #[Computed]
    public function apartments(): Collection
    {
        return Apartment::query()->ordered()->get();
    }

    /**
     * Future reservations of the apartment open in the modal: they block
     * deactivating it.
     *
     * @return Collection<int, Reservation>
     */
    #[Computed]
    public function futureReservations(): Collection
    {
        return $this->form->apartment
            ? $this->form->apartment->futureReservations()->with(['guest', 'employee'])->get()
            : new Collection;
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->form->startNew(((int) Apartment::max('sort_order')) + 10);
        $this->showForm = true;
    }

    public function edit(Apartment $apartment): void
    {
        $this->resetValidation();
        $this->form->fillFrom($apartment);
        $this->showForm = true;
    }

    public function save(
        CreateApartment $createApartment,
        UpdateApartment $updateApartment,
        ActivateApartment $activateApartment,
        DeactivateApartment $deactivateApartment,
    ): void {
        $this->form->validate();

        $apartment = $this->form->apartment;

        DB::transaction(function () use ($apartment, $createApartment, $updateApartment, $activateApartment, $deactivateApartment) {
            if ($apartment === null) {
                $created = $createApartment($this->form->payload());

                if (! $this->form->is_active) {
                    $deactivateApartment($created);
                }

                return;
            }

            $updateApartment($apartment, $this->form->payload());

            if ($apartment->is_active !== $this->form->is_active) {
                $this->form->is_active
                    ? $activateApartment($apartment)
                    : $deactivateApartment($apartment);
            }
        });

        $this->showForm = false;
        unset($this->apartments);

        Flux::toast(variant: 'success', text: $apartment === null
            ? __('apartments.created', ['number' => $this->form->number])
            : __('apartments.updated', ['number' => $this->form->number]));
    }

    public function render(): View
    {
        return view('livewire.apartments.index')
            ->title(__('apartments.title'));
    }
}
