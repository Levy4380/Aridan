<?php

namespace App\Livewire\Guests;

use App\Domain\PhoneNormalizer;
use App\Models\Guest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Guests screen (/clientes): the searchable list. The guest file is the
 * Guests\Drawer, mounted in the layout and opened with `open-guest`.
 */
class Index extends Component
{
    /** Shown at most; the search narrows it down. */
    public const LIMIT = 100;

    #[Url(as: 'buscar')]
    public string $search = '';

    /**
     * Guests matching the search by name, phone or DNI/CUIT. Dots, dashes
     * and spaces are ignored; phones also match however they are written.
     *
     * @return Collection<int, Guest>
     */
    #[Computed]
    public function guests(): Collection
    {
        $term = trim($this->search);
        $digits = Guest::digits($term);

        return Guest::query()
            ->withCount('reservations')
            // The last stay already started (future bookings aren't a stay yet).
            ->withMax(['reservations as last_check_in' => fn (Builder $query) => $query->where('check_in_date', '<=', today())], 'check_in_date')
            ->when($term !== '', function (Builder $query) use ($term, $digits) {
                $query->where(function (Builder $query) use ($term, $digits) {
                    // Every word of the search has to be in the name.
                    $query->where(function (Builder $query) use ($term) {
                        foreach (preg_split('/\s+/', mb_strtolower($term)) as $word) {
                            $query->whereRaw("lower(first_name || ' ' || last_name) like ?", ['%'.$word.'%']);
                        }
                    });

                    if (strlen($digits) >= 3) {
                        $query->orWhere('phone', 'like', '%'.$digits.'%')
                            ->orWhere('document_number', 'like', '%'.$digits.'%')
                            ->orWhere('phone', PhoneNormalizer::normalize($term));
                    }
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::LIMIT)
            ->get();
    }

    #[On('guest-changed')]
    #[On('reservation-changed')]
    public function refresh(): void
    {
        unset($this->guests);
    }

    public function render(): View
    {
        return view('livewire.guests.index')->title(__('guests.title'));
    }
}
