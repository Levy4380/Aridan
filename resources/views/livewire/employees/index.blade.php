<div class="flex flex-col gap-4">
    <x-page-header :title="__('employees.title')" :description="__('employees.description')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="$dispatch('open-employee')" data-test="new-employee-button">
                {{ __('employees.new') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="overflow-x-auto rounded-ui border border-border bg-surface">
        <table class="stack-table w-full min-w-[560px] text-sm">
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
                                wire:click="$dispatch('open-employee', { id: {{ $employee->id }} })"
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
                        <td data-label="{{ __('employees.columns.email') }}" class="px-4 py-2 text-ink-2">{{ $employee->email }}</td>
                        <td data-label="{{ __('employees.columns.status') }}" class="px-4 py-2"><x-status-badge :active="$employee->is_active" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
