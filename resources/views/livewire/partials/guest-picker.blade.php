{{-- Guest picker (arquitectura-frontend.md, 5.5). Requires the PicksGuest trait. --}}
<div class="flex flex-col gap-2">
    @if ($this->selectedGuest)
        <x-guest-card :guest="$this->selectedGuest">
            <flux:button size="sm" variant="ghost" wire:click="changeGuest">{{ __('guests.picker.change') }}</flux:button>
        </x-guest-card>
    @elseif ($creatingGuest)
        <div class="flex flex-col gap-3 rounded-ui border border-border-control p-3">
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input wire:model.blur="guest.first_name" :label="__('reservations.fields.first_name')" required />
                <flux:input wire:model.blur="guest.last_name" :label="__('reservations.fields.last_name')" required />
                <flux:input wire:model.live.debounce.400ms="guest.phone" :label="__('reservations.fields.phone')" type="tel" required />
                <flux:input wire:model.live.debounce.400ms="guest.document_number" :label="__('reservations.fields.document')" :badge="__('guests.optional')" />
            </div>

            @foreach (array_filter([$this->phoneOwner, $this->documentOwner]) as $owner)
                <flux:callout variant="warning" icon="exclamation-triangle" wire:key="owner-{{ $owner->id }}">
                    <flux:callout.text>
                        {{ $owner->is($this->phoneOwner) ? __('guests.picker.phone_owner', ['name' => $owner->full_name]) : __('guests.picker.document_owner', ['name' => $owner->full_name]) }}
                    </flux:callout.text>
                    <x-slot name="actions">
                        <flux:button size="sm" wire:click="selectGuest({{ $owner->id }})">{{ __('guests.picker.use_existing') }}</flux:button>
                    </x-slot>
                </flux:callout>
                @break
            @endforeach

            <p class="text-[13px] text-subtle">{{ __('guests.picker.new_help') }}</p>
            <div>
                <flux:button size="sm" variant="ghost" wire:click="cancelNewGuest">{{ __('guests.picker.back_to_search') }}</flux:button>
            </div>
        </div>
    @else
        <flux:input
            wire:model.live.debounce.300ms="guestSearch"
            icon="magnifying-glass"
            :placeholder="__('guests.picker.search')"
            :aria-label="__('guests.picker.search')"
            autocomplete="off"
        />

        @if ($this->foundGuest)
            <x-guest-card :guest="$this->foundGuest">
                <flux:button size="sm" variant="primary" wire:click="selectGuest({{ $this->foundGuest->id }})" data-test="select-guest-button">{{ __('guests.picker.select') }}</flux:button>
            </x-guest-card>
        @elseif (\App\Models\Guest::digits($guestSearch) !== '' && strlen(\App\Models\Guest::digits($guestSearch)) >= 6)
            <p class="text-[13px] text-subtle">{{ __('guests.picker.not_found') }}</p>
        @endif

        <div>
            <flux:button size="sm" variant="ghost" icon="plus" wire:click="startNewGuest" data-test="new-guest-button">{{ __('guests.picker.new') }}</flux:button>
        </div>
    @endif

    <flux:error name="guest_id" />
    <flux:error name="guest.phone" />
    <flux:error name="guest.document_number" />
</div>
