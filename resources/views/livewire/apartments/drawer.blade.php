<div>
        <flux:modal variant="flyout" wire:model.self.live="show" data-test="apartment-drawer" class="w-full max-md:max-w-none max-md:p-5 md:w-[560px]">
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
                                <li class="border-b border-divider-soft last:border-0">
                                    <button type="button" wire:click="$dispatch('open-reservation', { id: {{ $reservation->id }} })" class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-accent-softer">
                                    <span class="size-3 shrink-0 rounded-[2px] border border-[rgba(20,22,26,0.2)]" style="background-color: {{ $reservation->employee->color }}"></span>
                                    <span class="truncate font-medium text-ink">{{ $reservation->guest->full_name }}</span>
                                    <span class="ms-auto shrink-0 text-subtle">
                                        {{ \App\Support\Format::date($reservation->check_in_date) }}–{{ \App\Support\Format::date($reservation->check_out_date) }}
                                    </span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($form->apartment && $confirmingDelete)
                    @php($future = $this->futureReservations->count())
                    <x-delete-confirmation
                        :title="__('apartments.delete.question', ['number' => $form->apartment->number])"
                        :confirm="$future === 0 ? 'delete' : null"
                        :confirm-label="__('apartments.delete.confirm')"
                        :back-label="__('apartments.delete.back')"
                        data-test="delete-confirmation"
                    >
                        @if ($future > 0)
                            <p>{{ trans_choice('apartments.delete.blocked', $future, ['count' => $future]) }}</p>
                        @else
                            <p>{{ __('apartments.delete.help') }}</p>
                            @if ($this->pastReservationCount > 0)
                                <p class="font-semibold">{{ trans_choice('apartments.delete.with_reservations', $this->pastReservationCount, ['count' => $this->pastReservationCount]) }}</p>
                            @endif
                        @endif
                        <flux:error name="delete" />
                    </x-delete-confirmation>
                @endif

                <div class="-mx-5 -mb-5 px-5 md:-mx-6 md:-mb-6 md:px-6 flex flex-wrap justify-end gap-2 border-t border-divider bg-surface-alt py-4">
                    @if ($form->apartment && ! $confirmingDelete)
                        <flux:button variant="ghost" icon="trash" wire:click="startDelete" class="border border-danger-border text-danger! max-sm:w-full sm:me-auto" data-test="delete-button">
                            {{ __('apartments.delete.button') }}
                        </flux:button>
                    @endif
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
