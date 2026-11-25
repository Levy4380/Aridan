<?php

namespace App\Livewire\Guests;

use App\Actions\Guests\SaveGuest;
use App\Models\Guest;
use App\Models\Reservation;
use App\Support\Format;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Guest drawer: data + reservation history (the "historial del cliente"
 * CLAUDE.md asks for). Mounted once in the app layout, so it opens over any
 * screen with the `open-guest` event and closing it leaves you where you
 * were. ?cliente=45 opens it directly on any page.
 */
class Drawer extends Component
{
    #[Url(as: 'cliente')]
    public ?int $guestId = null;

    public bool $show = false;

    public bool $creating = false;

    /** @var array{first_name: string, last_name: string, phone: string, document_number: string} */
    public array $form = ['first_name' => '', 'last_name' => '', 'phone' => '', 'document_number' => ''];

    public function mount(): void
    {
        if ($this->guestId && Guest::whereKey($this->guestId)->exists()) {
            $this->open($this->guestId);
        } else {
            $this->guestId = null;
        }
    }

    /**
     * Opens a guest, or a blank form for a new one (prefilled with what was
     * searched, when it has digits).
     */
    #[On('open-guest')]
    public function open(?int $id = null, string $prefill = ''): void
    {
        $this->resetValidation();

        if ($id === null) {
            $this->guestId = null;
            $this->creating = true;
            $this->form = ['first_name' => '', 'last_name' => '', 'phone' => '', 'document_number' => ''];

            if (Guest::digits($prefill) !== '') {
                $this->form['phone'] = trim($prefill);
            }
        } else {
            $guest = Guest::findOrFail($id);

            $this->guestId = $guest->id;
            $this->creating = false;
            $this->form = [
                'first_name' => $guest->first_name,
                'last_name' => $guest->last_name,
                'phone' => Format::phone($guest->phone),
                'document_number' => (string) $guest->document_number,
            ];
        }

        unset($this->guest, $this->history);
        $this->show = true;
    }

    public function updatedShow(bool $show): void
    {
        if (! $show) {
            $this->guestId = null;
            $this->creating = false;
        }
    }

    #[Computed]
    public function guest(): ?Guest
    {
        return $this->guestId ? Guest::find($this->guestId) : null;
    }

    /**
     * Every reservation of the open guest, cancelled ones included.
     *
     * @return Collection<int, Reservation>
     */
    #[Computed]
    public function history(): Collection
    {
        return $this->guestId
            ? Reservation::withTrashed()
                ->where('guest_id', $this->guestId)
                ->with(['apartment', 'employee'])
                ->orderByDesc('check_in_date')
                ->get()
            : new Collection;
    }

    /** A reservation changed (moved, cancelled...) while the history is open. */
    #[On('reservation-changed')]
    public function refresh(): void
    {
        unset($this->history);
    }

    public function save(SaveGuest $saveGuest): void
    {
        try {
            $guest = $saveGuest($this->form, $this->creating ? null : $this->guest);
        } catch (ValidationException $e) {
            // The action speaks in field names; the form lives under "form.".
            throw ValidationException::withMessages(
                collect($e->errors())->mapWithKeys(fn ($messages, $field) => ['form.'.$field => $messages])->all(),
            );
        }

        Flux::toast(variant: 'success', text: $this->creating
            ? __('guests.created', ['name' => $guest->full_name])
            : __('guests.updated', ['name' => $guest->full_name]));

        $this->dispatch('guest-changed');
        $this->open($guest->id);
    }

    public function render(): View
    {
        return view('livewire.guests.drawer');
    }
}
