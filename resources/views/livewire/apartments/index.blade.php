<div class="flex flex-col gap-4">
    <x-page-header
        :title="__('apartments.title')"
        :description="__('apartments.description', ['active' => $this->apartments->where('is_active', true)->count(), 'total' => $this->apartments->count()])"
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="create" data-test="new-apartment-button">
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
                    <tr wire:key="apartment-{{ $apartment->id }}" class="border-b border-divider-soft last:border-0 hover:bg-accent-softer">
                        <td class="px-4 py-2">
                            <button
                                type="button"
                                wire:click="edit({{ $apartment->id }})"
                                class="text-[15px] font-bold hover:text-accent {{ $apartment->is_active ? 'text-ink' : 'text-disabled' }}"
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

    <flux:modal wire:model.self="showForm" class="w-full md:w-[580px]">
        <form wire:submit="save" class="flex flex-col gap-5">
            <div class="flex items-center gap-3">
                <h2 class="text-[20px] font-bold text-ink">
                    {{ $form->apartment ? __('apartments.modal.edit_title', ['number' => $form->apartment->number]) : __('apartments.modal.create_title') }}
                </h2>
                @if ($form->apartment)
                    <x-status-badge :active="$form->apartment->is_active" />
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="form.number" :label="__('apartments.fields.number')" required autofocus />
                <flux:input wire:model="form.capacity" :label="__('apartments.fields.capacity')" type="number" min="1" required />
                <flux:input wire:model="form.sort_order" :label="__('apartments.fields.sort_order')" :description="__('apartments.fields.sort_order_help')" type="number" min="0" required />
            </div>

            <flux:textarea wire:model="form.description" :label="__('apartments.fields.description')" :placeholder="__('apartments.fields.description_placeholder')" rows="2" />

            @php($blocked = $form->apartment?->is_active && $this->futureReservations->isNotEmpty())

            <div class="flex flex-col gap-2">
                <flux:switch wire:model="form.is_active" :label="__('apartments.fields.active')" :description="__('apartments.fields.active_help')" :disabled="$blocked" align="left" />
                <flux:error name="is_active" />

                @if ($blocked)
                    <flux:callout variant="warning" icon="exclamation-triangle" data-test="cannot-deactivate-warning">
                        <flux:callout.text>
                            {{ trans_choice('apartments.cannot_deactivate', $this->futureReservations->count(), ['count' => $this->futureReservations->count()]) }}
                        </flux:callout.text>
                    </flux:callout>
                @endif
            </div>

            @if ($this->futureReservations->isNotEmpty())
                <div class="flex flex-col gap-1.5">
                    <p class="text-[13px] font-bold uppercase tracking-[0.04em] text-muted">{{ __('apartments.future_reservations') }}</p>
                    <ul class="flex max-h-48 flex-col overflow-y-auto rounded-ui border border-divider">
                        @foreach ($this->futureReservations as $reservation)
                            <li class="flex items-center gap-2 border-b border-divider-soft px-3 py-1.5 text-sm last:border-0">
                                <span class="size-3 shrink-0 rounded-[2px] border border-[rgba(20,22,26,0.2)]" style="background-color: {{ $reservation->employee->color }}"></span>
                                <span class="truncate font-medium text-ink">{{ $reservation->guest->full_name }}</span>
                                <span class="ms-auto shrink-0 text-subtle">
                                    {{ \App\Support\Format::date($reservation->check_in_date) }}–{{ \App\Support\Format::date($reservation->check_out_date) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="-mx-6 -mb-6 flex justify-end gap-2 border-t border-divider bg-surface-alt px-6 py-4">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('common.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit" data-test="save-apartment-button">
                    {{ $form->apartment ? __('common.save_changes') : __('apartments.modal.create') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
