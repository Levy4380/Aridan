@php
    use App\Support\Format;

    $stretch = "after:absolute after:inset-0 after:content-[''] focus-visible:outline-none focus-visible:after:outline-2 focus-visible:after:-outline-offset-2 focus-visible:after:outline-accent";
@endphp

<div class="flex flex-col gap-4">
    <x-page-header :title="__('guests.title')" :description="__('guests.description')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="$dispatch('open-guest', { prefill: $wire.search })" data-test="new-guest-button">{{ __('guests.new') }}</flux:button>
        </x-slot:actions>
    </x-page-header>

    <flux:input
        wire:model.live.debounce.300ms="search"
        icon="magnifying-glass"
        :placeholder="__('guests.search')"
        :aria-label="__('guests.search')"
        class="max-w-md"
        autocomplete="off"
        clearable
    />

    <div class="overflow-x-auto rounded-ui border border-border bg-surface" wire:loading.class="opacity-60" wire:target="search">
        <table class="w-full min-w-[720px] text-sm">
            <thead class="bg-surface-alt text-left text-xs font-semibold text-muted">
                <tr class="border-b border-divider">
                    <th scope="col" class="px-4 py-2.5">{{ __('guests.columns.name') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('guests.columns.phone') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('guests.columns.document') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('guests.columns.reservations') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('guests.columns.last_stay') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->guests as $guest)
                    <tr wire:key="guest-{{ $guest->id }}" class="relative cursor-pointer border-b border-divider-soft last:border-0 hover:bg-accent-softer">
                        <td class="px-4 py-2">
                            <button
                                type="button"
                                wire:click="$dispatch('open-guest', { id: {{ $guest->id }} })"
                                class="font-semibold text-ink {{ $stretch }}"
                                aria-label="{{ __('guests.open_label', ['name' => $guest->full_name]) }}"
                            >{{ $guest->full_name }}</button>
                        </td>
                        <td class="px-4 py-2 text-ink-2">{{ Format::phone($guest->phone) }}</td>
                        <td class="px-4 py-2 {{ $guest->document_number ? 'text-ink-2' : 'text-faint' }}">
                            {{ $guest->document_number ? Format::document($guest->document_number) : __('guests.no_document') }}
                        </td>
                        <td class="px-4 py-2 text-ink-2">{{ $guest->reservations_count }}</td>
                        <td class="px-4 py-2 text-ink-2">
                            {{ $guest->last_check_in ? Format::date(\Carbon\CarbonImmutable::parse($guest->last_check_in), true) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-subtle">{{ __('guests.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($this->guests->count() >= \App\Livewire\Guests\Index::LIMIT)
        <p class="text-[13px] text-subtle">{{ __('guests.limited', ['count' => \App\Livewire\Guests\Index::LIMIT]) }}</p>
    @endif

</div>
