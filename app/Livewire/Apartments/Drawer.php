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
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Apartment drawer (create / edit). Mounted once in the app layout: it
 * opens over any screen with the `open-apartment` event, and closing it
 * leaves you where you were.
 */
class Drawer extends Component
{
    public ApartmentForm $form;

    public bool $show = false;

    #[On('open-apartment')]
    public function open(?int $id = null): void
    {
        $this->resetValidation();

        if ($id === null) {
            $this->form->startNew(((int) Apartment::max('sort_order')) + 10);
        } else {
            $this->form->fillFrom(Apartment::findOrFail($id));
        }

        unset($this->futureReservations);
        $this->show = true;
    }

    /**
     * Future reservations of the open apartment: they block deactivating it.
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

    #[On('reservation-changed')]
    public function refresh(): void
    {
        unset($this->futureReservations);
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

        $this->show = false;
        $this->dispatch('apartment-changed');

        Flux::toast(variant: 'success', text: $apartment === null
            ? __('apartments.created', ['number' => $this->form->number])
            : __('apartments.updated', ['number' => $this->form->number]));
    }

    public function render(): View
    {
        return view('livewire.apartments.drawer');
    }
}
