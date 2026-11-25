<?php

namespace App\Livewire\Apartments;

use App\Models\Apartment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Apartments screen (/departamentos): the list. The apartment form is the
 * Apartments\Drawer, mounted in the layout and opened with `open-apartment`.
 */
class Index extends Component
{
    /**
     * @return Collection<int, Apartment>
     */
    #[Computed]
    public function apartments(): Collection
    {
        return Apartment::query()->ordered()->get();
    }

    #[On('apartment-changed')]
    public function refresh(): void
    {
        unset($this->apartments);
    }

    public function render(): View
    {
        return view('livewire.apartments.index')
            ->title(__('apartments.title'));
    }
}
