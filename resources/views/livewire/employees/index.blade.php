<div class="flex flex-col gap-4">
    <x-page-header :title="__('employees.title')" :description="__('employees.description')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="create" data-test="new-employee-button">
                {{ __('employees.new') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="overflow-x-auto rounded-ui border border-border bg-surface">
        <table class="w-full min-w-[560px] text-sm">
            <thead class="bg-surface-alt text-left text-xs font-semibold text-muted">
                <tr class="border-b border-divider">
                    <th scope="col" class="px-4 py-2.5">{{ __('employees.columns.employee') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('employees.columns.email') }}</th>
                    <th scope="col" class="px-4 py-2.5">{{ __('employees.columns.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->employees as $employee)
                    <tr wire:key="employee-{{ $employee->id }}" class="relative cursor-pointer border-b border-divider-soft last:border-0 hover:bg-accent-softer">
                        <td class="px-4 py-2">
                            <button
                                type="button"
                                wire:click="edit({{ $employee->id }})"
                                class="flex items-center gap-2.5 font-semibold after:absolute after:inset-0 after:content-[''] focus-visible:outline-none focus-visible:after:outline-2 focus-visible:after:-outline-offset-2 focus-visible:after:outline-accent {{ $employee->is_active ? 'text-ink' : 'text-disabled' }}"
                                aria-label="{{ __('employees.edit_label', ['name' => $employee->name]) }}"
                            >
                                <x-employee-avatar :user="$employee" :size="28" />
                                {{ $employee->name }}
                                @if ($employee->is(auth()->user()))
                                    <span class="text-xs font-medium text-subtle">({{ __('employees.you') }})</span>
                                @endif
                            </button>
                        </td>
                        <td class="px-4 py-2 text-ink-2">{{ $employee->email }}</td>
                        <td class="px-4 py-2"><x-status-badge :active="$employee->is_active" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <flux:modal variant="flyout" wire:model.self="showForm" class="w-full md:w-[560px]">
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

            <div class="-mx-6 -mb-6 flex justify-end gap-2 border-t border-divider bg-surface-alt px-6 py-4">
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
