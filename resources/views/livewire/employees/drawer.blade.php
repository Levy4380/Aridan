<div>
    <flux:modal variant="flyout" wire:model.self.live="show" data-test="employee-drawer" class="w-full max-md:max-w-none max-md:p-5 md:w-[560px]">
        <form wire:submit="save" class="flex flex-col gap-5">
            <div class="flex items-center gap-3">
                <h2 class="text-[20px] font-bold text-ink">
                    {{ $form->employee ? $form->employee->name : __('employees.modal.create_title') }}
                </h2>
                @if ($form->employee)
                    <x-status-badge :active="$form->employee->is_active" />
                @endif
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input wire:model="form.name" :label="__('employees.fields.name')" required autofocus />
                <flux:input wire:model="form.email" :label="__('employees.fields.email')" type="email" required />
                @unless ($form->employee)
                    <flux:input wire:model="form.password" :label="__('employees.fields.password')" :description="__('employees.fields.password_help')" type="password" required viewable />
                @endunless
            </div>

            <div class="flex flex-col gap-2">
                <div>
                    <p class="text-sm font-medium text-ink">{{ __('employees.fields.color') }}</p>
                    <p class="text-[13px] text-subtle">{{ __('employees.fields.color_help') }}</p>
                </div>

                <x-color-swatches :palette="$palette" :selected="$form->color" :taken-by="$this->takenColors" pick="pickColor" />

                <label class="flex w-fit items-center gap-2 text-[13px] text-muted">
                    <input type="color" wire:model.live="form.color" class="size-8 cursor-pointer rounded-[2px] border border-border-control bg-surface p-0.5" />
                    {{ __('employees.fields.other_color') }}
                </label>
                <flux:error name="form.color" />

                @if ($this->colorInUseBy)
                    <flux:callout variant="warning" icon="exclamation-triangle" data-test="color-in-use-warning">
                        <flux:callout.text>
                            {{ __('warnings.color_in_use', ['name' => collect($this->colorInUseBy)->pluck('name')->join(', ', ' y ')]) }}
                        </flux:callout.text>
                    </flux:callout>
                @endif
            </div>

            <div class="flex flex-col gap-1.5">
                <p class="text-xs font-semibold text-muted">{{ __('employees.preview') }}</p>
                <div
                    class="w-56 rounded-[3px] border border-[rgba(20,22,26,0.22)] px-2 py-1 text-[11px] font-medium leading-snug"
                    style="background-color: {{ $form->color ?: '#ffffff' }}; color: {{ \App\Support\ColorContrast::textOn($form->color ?: '#ffffff') }};"
                    aria-hidden="true"
                >
                    <div class="text-[13px] font-bold">{{ __('employees.preview_guest') }}</div>
                    <div>{{ __('employees.preview_payment') }}</div>
                    <div>{{ $form->name ?: __('employees.fields.name') }}</div>
                </div>
            </div>

            @if ($form->employee)
                <div class="flex flex-col gap-1">
                    <flux:switch
                        wire:model="form.is_active"
                        :label="__('employees.fields.active')"
                        :description="$this->isEditingSelf() ? __('employees.cannot_deactivate_self') : __('employees.fields.active_help')"
                        :disabled="$this->isEditingSelf()"
                        align="left"
                    />
                    <flux:error name="is_active" />
                </div>
            @endif

            <div class="-mx-5 -mb-5 px-5 md:-mx-6 md:-mb-6 md:px-6 flex justify-end gap-2 border-t border-divider bg-surface-alt py-4">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('common.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit" data-test="save-employee-button">
                    {{ $form->employee ? __('common.save_changes') : __('employees.modal.create') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
