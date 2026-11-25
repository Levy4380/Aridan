<div class="flex flex-col gap-4">
    <x-page-header
        :title="__('apartments.title')"
        :description="__('apartments.description', ['active' => $this->apartments->where('is_active', true)->count(), 'total' => $this->apartments->count()])"
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="$dispatch('open-apartment')" data-test="new-apartment-button">
                {{ __('apartments.new') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="overflow-x-auto rounded-ui border border-border bg-surface">
        <table class="w-full min-w-[640px] text-sm">
            <thead class="bg-surface-alt text-left text-xs font-semibold text-muted">
                <tr class="border-b border-divider">
                    <th scope="col" class="px-4 py-2.5">{{ __('apartments.columns.number') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('apartments.columns.capacity') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('apartments.columns.description') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('apartments.columns.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->apartments as $apartment)
                    <tr wire:key="apartment-{{ $apartment->id }}" class="relative cursor-pointer border-b border-divider-soft last:border-0 hover:bg-accent-softer">
                        <td class="px-4 py-2">
                            <button
                                type="button"
                                wire:click="$dispatch('open-apartment', { id: {{ $apartment->id }} })"
                                class="text-[15px] font-bold after:absolute after:inset-0 after:content-[''] focus-visible:outline-none focus-visible:after:outline-2 focus-visible:after:-outline-offset-2 focus-visible:after:outline-accent {{ $apartment->is_active ? 'text-ink' : 'text-disabled' }}"
                                aria-label="{{ __('apartments.edit_label', ['number' => $apartment->number]) }}"
                            >{{ $apartment->number }}</button>
                        </td>
                        <td class="px-4 py-2 text-ink-2">{{ trans_choice('apartments.people', $apartment->capacity) }}</td>
                        <td class="px-4 py-2 text-subtle">{{ $apartment->description ?? '—' }}</td>
                        <td class="px-4 py-2"><x-status-badge :active="$apartment->is_active" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-subtle">{{ __('apartments.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
