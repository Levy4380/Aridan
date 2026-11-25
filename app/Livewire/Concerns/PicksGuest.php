<?php

namespace App\Livewire\Concerns;

use App\Domain\GuestLookup;
use App\Domain\PhoneNormalizer;
use App\Models\Guest;
use Livewire\Attributes\Computed;

/**
 * The guest picker (arquitectura-frontend.md, 5.5), shared by the forms that
 * need a reservation holder: search an existing guest by phone or DNI/CUIT,
 * or load a new one right there. A new guest is created together with the
 * reservation, never on its own. View: livewire/partials/guest-picker.
 */
trait PicksGuest
{
    public string $guestSearch = '';

    public ?int $guest_id = null;

    public bool $creatingGuest = false;

    /** @var array{first_name: string, last_name: string, phone: string, document_number: string} */
    public array $guest = ['first_name' => '', 'last_name' => '', 'phone' => '', 'document_number' => ''];

    #[Computed]
    public function selectedGuest(): ?Guest
    {
        return $this->guest_id ? Guest::withCount('reservations')->find($this->guest_id) : null;
    }

    /**
     * The guest matching what was typed in the search box, if any.
     */
    #[Computed]
    public function foundGuest(): ?Guest
    {
        return app(GuestLookup::class)->find($this->guestSearch)?->loadCount('reservations');
    }

    /**
     * While loading a new guest: an existing one with that phone.
     */
    #[Computed]
    public function phoneOwner(): ?Guest
    {
        return $this->creatingGuest ? app(GuestLookup::class)->byPhone($this->guest['phone']) : null;
    }

    /**
     * While loading a new guest: an existing one with that DNI/CUIT.
     */
    #[Computed]
    public function documentOwner(): ?Guest
    {
        return $this->creatingGuest ? app(GuestLookup::class)->byDocument($this->guest['document_number']) : null;
    }

    public function selectGuest(int $guestId): void
    {
        $this->guest_id = Guest::findOrFail($guestId)->id;
        $this->creatingGuest = false;
        $this->guestSearch = '';
        $this->resetErrorBag(['guest_id', 'guest.first_name', 'guest.last_name', 'guest.phone', 'guest.document_number']);
    }

    public function changeGuest(): void
    {
        $this->guest_id = null;
        $this->creatingGuest = false;
    }

    public function startNewGuest(): void
    {
        $this->creatingGuest = true;
        $this->guest_id = null;
        $this->guest = ['first_name' => '', 'last_name' => '', 'phone' => '', 'document_number' => ''];

        // Prefill with what was searched.
        if (GuestLookup::looksLikePhone($this->guestSearch)) {
            $this->guest['phone'] = $this->guestSearch;
        } elseif (Guest::digits($this->guestSearch) !== '') {
            $this->guest['document_number'] = $this->guestSearch;
        }
    }

    public function cancelNewGuest(): void
    {
        $this->creatingGuest = false;
    }

    protected function resetGuestPicker(): void
    {
        $this->guestSearch = '';
        $this->guest_id = null;
        $this->creatingGuest = false;
        $this->guest = ['first_name' => '', 'last_name' => '', 'phone' => '', 'document_number' => ''];
    }

    /**
     * @return array<string, mixed> the guest part of a reservation payload
     */
    protected function guestPayload(): array
    {
        return $this->creatingGuest
            ? ['guest_id' => null, 'guest' => $this->guest]
            : ['guest_id' => $this->guest_id, 'guest' => null];
    }

    /**
     * Normalized phone of the guest being loaded (for the similar-name warning).
     */
    protected function newGuestPhone(): string
    {
        return PhoneNormalizer::normalize($this->guest['phone']);
    }
}
